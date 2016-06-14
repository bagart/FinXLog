<?php
namespace FinXLog\Module\Ratchet;

use FinXLog\Exception\WrongParams;
use FinXLog\Model\QuotationAgg;
use FinXLog\Module\ClientQueue\SearchQuotation;
use FinXLog\Module\Logger;
use FinXLog\Traits\WithQueueConnector;
use FinXLog\Module\Connector;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\Version\RFC6455;

class QuotationWebSocketDelivery implements \Ratchet\MessageComponentInterface
{
    use WithQueueConnector;

    const QUOTATION_ALL = SearchQuotation::QUOTATION_ALL;
    /**
     * @var \SplObjectStorage|ConnectionInterface[]
     */
    protected $clients = [];

    /**
     * @var ConnectionInterface[][][]
     */
    protected $subscribers = [];

    /**
     * @var bool[]
     */
    protected $service_by_resource;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;

    }

    /**
     * is service => allow incoming
     * @param ConnectionInterface $conn
     * @return bool
     */
    protected function isService(ConnectionInterface $conn, $msg = null)
    {
        if (
            $conn->resourceId
            && isset($this->service_by_resource[$conn->resourceId])
        ) {
            return $this->service_by_resource[$conn->resourceId];
        }
        assert(strlen(
            getenv('FINXLOG_WEBSOCKET_SERVICE_PATH')
            . getenv('FINXLOG_WEBSOCKET_SERVICE_FILTER_ADDR_REGEXP')
        ));

        $is_service = true;

        if (strlen(getenv('FINXLOG_WEBSOCKET_SERVICE_FILTER_ADDR_REGEXP'))) {
            $is_service = (bool)preg_match(
                '~' . getenv('FINXLOG_WEBSOCKET_SERVICE_FILTER_ADDR_REGEXP') . '~iu',
                $conn->remoteAddress
            );
        }

        try {
            if (getenv('FINXLOG_WEBSOCKET_SERVICE_PATH')) {
                $is_service = (
                    $is_service
                    && $conn instanceof RFC6455\Connection
                    && $conn->WebSocket->request
                    && strstr(
                        $conn->WebSocket->request->getPath(),
                        getenv('FINXLOG_WEBSOCKET_SERVICE_PATH'),
                        1
                    )
                );
            }
        } catch (\Throwable $e) {
            Logger::error(
                $e->getMessage(),
                ['e' => $e]
            );

            $is_service = false;
        }

        return $this->service_by_resource[$conn->resourceId] = $is_service;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        if (!$this->isService($conn)) {
            //waiting for manual subscribe
            //$this->clients['quotation'][static::QUOTATION_ALL]->attach($conn);
        }

        Logger::log()->debug(
            "WS: New "
            . (
                $this->isService($conn)
                    ? 'service'
                    : 'client'
            )
            . " connection: {$conn->resourceId}",
            [
                'is_service' => $this->isService($conn),
                'conn' => $conn
            ]
        );
    }

    public function getPreparedMessage(ConnectionInterface $conn, $msg)
    {
        $message = json_decode($msg, true);
        if (
            empty($message)
            || !is_array($message)
            || empty($message['type'])
        ) {
            throw new WrongParams("WS: !msg: $msg");
        }
        if (
            !empty($message['quotation'])
            && !preg_match('~^[\w\d_\.]+$~u', $message['quotation'])
        ) {
            throw (new WrongParams("WS: wrong quotation: $msg"))
                ->setParams(['quotation']);
        }
        if ($message['type'] == 'subscribe' && empty($message['quotation'])) {
            $message['quotation'] = static::QUOTATION_ALL;
        }

        if (
            !empty($message['doji'])
            && !preg_match('~^[\w\d_\.]+$~u', $message['doji'])
        ) {
            throw (new WrongParams("WS: wrong doji: $msg"))
                ->setParams(['doji']);
        }

        return $message;
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        Logger::log()->debug(
            "WS msg from #{$conn->resourceId}: {$msg}",
            [
                'conn' => $conn,
                'is_service' => $this->isService($conn),
                'conn_count' => count($this->service_by_resource),
            ]
        );

        if (!$this->isService($conn) && strlen($msg) > 1000) {
            Logger::log()->warning(
                "WS: msg with " . round(strlen($msg) / 1000). "kb",
                [
                    'conn' => $conn,
                    'is_service' => $this->isService($conn),
                    'conn_count' => count($this->service_by_resource),
                ]
            );
        }

        try {
            $message = $this->getPreparedMessage($conn, $msg);
            if ($this->isService($conn, $message)) {
                $this->addServiceIncoming($conn, $message);
            }
            //allow service  subscribe
            $this->addClientIncoming($conn, $message);
        } catch (\Throwable $e) {
            Logger::log()->warning(
                "WS: {$e->getMessage()} from "
                . (
                    $this->isService($conn)
                        ? 'service'
                        : 'client'
                )
                . " {$conn->resourceId}: : {$msg}",
                [
                    'e' => $e,
                    'conn' => $conn,
                    'is_service' => $this->isService($conn),
                    'conn_count' => count($this->service_by_resource),
                ]
            );
        }
    }

    /**
     * delivery to clients
     * @param ConnectionInterface $conn
     * @param string $msg
     */
    protected function addServiceIncoming(ConnectionInterface $conn, array $message)
    {
        assert($this->isService($conn));
        assert(!empty($message['type']));

        switch ($message['type']) {
            case 'status':
            case 'stat':
                $conn->send(json_encode([
                    'conn_count' => count($this->service_by_resource),
                ]));
                break;
            case 'all':
                foreach ($this->clients as $client) {
                    if (!$this->isService($client)) {
                        $conn->send(json_encode($message));
                    }
                }
                break;
            case 'send':
                foreach (
                    $this->subscribers
                        [$message['quotation']]
                        [empty($message['agg_period']) ? 0 : $message['agg_period']]
                     as $subscriber
                ) {
                    $subscriber['ws']->send(json_encode($message));
                }
                break;
        }
    }

    /**
     * set client opts
     * @param ConnectionInterface $conn
     * @param string $msg
     */
    protected function addClientIncoming(ConnectionInterface $conn, array $message)
    {
        assert(!empty($message['type']));
        if ($message['type'] != 'subscribe') {
            return false;
        }
        $agg_period = 0;
        if (!empty($message['doji'])) {
            $all_period = (new QuotationAgg)->getAggPeriod()[strtoupper($message['doji'])];
            if (empty($all_period[strtoupper($message['doji'])])) {
                throw (new WrongParams('WS: wrong doji period on subscribe'))
                    ->setParams(['doji']);
            }
            $agg_period = $all_period[strtoupper($message['doji'])];
        }
        /**
         * [USDEUR][3600][#123] = [ ... ];
         */
        $this->subscribers
            [$message['quotation']]
            [$agg_period]
            [$conn->resourceId]
            = [
                'ws' => $conn,
                'agg' => !empty($message['doji']) ? 'doji' : null,
        ];

        //load previous period
        $this->addJob([
            'quotation' => $message['quotation'],
            'agg' => !empty($message['doji']) ? 'doji' : null,
            'agg_period' => !empty($message['doji']) ? $message['doji'] : null,
        ]);

        //@todo add queue
   }

    public function onClose(ConnectionInterface $conn)
    {
        Logger::log()->debug(
            "WS:Disconnect with #{$conn->resourceId}",
            [
                'conn' => $conn,
            ]
        );

        unset($this->service_by_resource[$conn->resourceId]);

        foreach ($this->subscribers as $k1 => $v1) {
            foreach ($v1 as $k2=>$v2) {
                unset($this->subscribers[$k1][$k2][$conn->resourceId]);
            }
            $this->subscribers[$k1] = array_filter($this->subscribers[$k1]);
        }
        $this->subscribers = array_filter($this->subscribers);
        $this->clients->detach($conn);

    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Logger::log()->debug(
            "WS: " .get_class($e) . ": {$e->getMessage()} with #{$conn->resourceId}",
            [
                //'this', $this,
                'e' => $e,
                'conn' => $conn
            ]
        );
        $conn->close();
    }

    public function getDefaultQueueConnector()
    {
        return new Connector\Queue(
            getenv('FINXLOG_AMQP_TUBE_WS')
        );
    }

    public function addJob(array $array)
    {
        $this->getQueueConnector()
            ->put($array);

        return $this;
    }
}
