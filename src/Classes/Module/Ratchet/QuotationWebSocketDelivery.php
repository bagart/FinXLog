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
    protected $clients;

    /**
     * @var Subscribers
     */
    protected $subscribers;

    /**
     * @var bool[]
     */
    protected $service_by_resource;

    public function getClients()
    {
        if (!$this->clients) {
            $this->clients = new \SplObjectStorage;
        }

        return $this->clients;
    }

    /**
     * is service => allow incoming
     * @param ConnectionInterface|RFC6455\Connection $conn
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

        $is_service = true;

        if (strlen(getenv('FINXLOG_WEBSOCKET_SERVICE_FILTER_ADDR_REGEXP'))) {
            $is_service = $is_service && (bool) preg_match(
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
                    && false !== strstr(
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

    /**
     * @param ConnectionInterface|RFC6455\Connection $conn
     */
    public function onOpen(ConnectionInterface $conn)
    {
        if (getenv('FINXLOG_DEBUG')) {
            Logger::log()->info(
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
    }

    public function getPreparedMessage($msg, ConnectionInterface $conn = null)
    {
        $message = json_decode($msg, true);
        if (
            empty($message)
            || !is_array($message)
            || empty($message['type'])
        ) {
            throw new WrongParams("WS: !msg[type]: " .var_export($msg, true));
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
            !empty($message['agg_period'])
            && !preg_match('~^[\w\d_\.]+$~u', $message['agg_period'])
        ) {
            throw (new WrongParams("WS: wrong agg_period: $msg"))
                ->setParams(['agg_period']);
        }

        return $message;
    }

    /**
     * @param ConnectionInterface|RFC6455\Connection $conn
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        if (getenv('FINXLOG_DEBUG')) {
            Logger::log()->info(
                "WS " . ($this->isService($conn) ? 'service' : 'client').
                    " msg from #{$conn->resourceId}:" . mb_substr($msg, 0, 100) . '...',
                [
                    'conn' => $conn,
                    'is_service' => $this->isService($conn),
                    'conn_count' => count($this->service_by_resource),
                ]
            );
        }

        if (!$this->isService($conn) && strlen($msg) > 100000) {
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
            $message = $this->getPreparedMessage($msg, $conn);
            if ($this->isService($conn, $message)) {
                $this->addServiceIncoming($conn, $message);
            }
            //allow service  subscribe
            $this->addClientIncoming($conn, $message);
        } catch (\Throwable $e) {
            $error_message = ['type' => 'error'];
            if (getenv('FINXLOG_DEBUG') && getenv('FINXLOG_DEBUG') < 300 /* \Monolog\Logger::NOTICE */) {
                $error_message['error'] = [
                    'class' => get_class($e),
                    'message' => $e->getMessage(),
                ];
            }
            $conn->send(json_encode($error_message));
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
     * @param ConnectionInterface|RFC6455\Connection $conn
     * @param array $message
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
                foreach ($this->getClients() as $client) {
                    if (!$this->isService($client)) {
                        $conn->send(json_encode($message));
                    }
                }
                break;
            case 'send':
                foreach (
                    $this->getSubscribers()->get($message)
                    as $subscribe
                ) {
                    $subscribe['ws']->send(json_encode(
                        ['type' => 'subscribe']
                        + $message
                    ));
                }
                Logger::log()->debug(':2js:');

                break;
        }
    }

    /**
     * @return Subscribers
     */
    public function getSubscribers()
    {
        if (!$this->subscribers) {
            $this->subscribers = new Subscribers;
        }

        return $this->subscribers;
    }

    protected function checkPeriod($period, $field_name = null)
    {
        if (
            strlen($period)
            && !is_numeric($period)
            && empty((new QuotationAgg)->getAggPeriod()[$period])
        ) {
            $e = new WrongParams(
                "WS: wrong period: $period"
                . ($field_name !== null ? " field: $field_name" : '')
            );
            if ($field_name !== null) {
                $e->setParams($field_name);
            }

            throw $e;
        }

        return $this;
    }

    /**
     * set client opts
     * @param ConnectionInterface|RFC6455\Connection $conn
     * @param array $message
     */
    protected function addClientIncoming(ConnectionInterface $conn, array $message)
    {
        if (empty($message['type'])) {
            Logger::log()->warning('WS client? message without type');
            return $this;
        }

        switch ($message['type']) {
            case 'quotations':
                $conn->send(json_encode([
                    "type" => 'quotations',
                    'quotations' => ['BTCUSD','USDBTC','USDEUR','EURUSD']
                ]));
                break;
            case 'subscribe':
                if (!empty($message['agg_period'])) {
                    $this->checkPeriod($message['agg_period']);
                }
                $this->getSubscribers()
                    ->add(['ws' => $conn] + $message);

                //load previous period
                $this->addJob($message);
                Logger::log()->info('AMQP add:' . json_encode($message));
                break;
            case 'unsubscribe':
                $this->getSubscribers()->drop($conn, $message);
                Logger::log()->info('AMQP unsubscribe');
                break;
        }

        return $this;
   }

    /**
     * @param ConnectionInterface|RFC6455\Connection $conn
     */
    public function onClose(ConnectionInterface $conn)
    {
        if (getenv('FINXLOG_DEBUG')) {
            Logger::log()->info(
                "WS:Disconnect with #{$conn->resourceId}",
                [
                    'conn' => $conn,
                ]
            );
        }

        unset($this->service_by_resource[$conn->resourceId]);
        $this->getSubscribers()->drop($conn);
        $this->getClients()->detach($conn);
    }

    /**
     * @param ConnectionInterface|RFC6455\Connection $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        if (getenv('FINXLOG_DEBUG')) {
            Logger::log()->info(
                "WS: " . get_class($e) . ": {$e->getMessage()} with #{$conn->resourceId}",
                [
                    'e' => $e,
                    'conn' => $conn
                ]
            );
        }
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
