<?php
namespace FinXLog\Module\ClientQueue;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\ImportQuotation;
use FinXLog\Module\Logger;
use FinXLog\Traits;
use FinXLog\Model;
use Pheanstalk\Job;

/**
 * make layer for different import source
 * Class SaveQuotation
 * @package FinXLog\Module\Import
 */
abstract class AbsQuotation
{
    use Traits\WithQueueConnector;

    private $model_quotation = null;
    private $ws_connector = null;


    public function setWsConnector($ws_connector)
    {
        $this->ws_connector = $ws_connector;

        return $this;
    }
    public function getWsConnector()
    {
        if (!$this->ws_connector) {
            $this->ws_connector = $this->getDefaultWsConnector();
        }

        return $this->ws_connector;
    }

    public function addWsMessage($message)
    {
        $this->getWsConnector()
            ->then(
                function($conn) use ($message) {
                    $conn->send($message);
                },
                function ($e) {
                    Logger::error(
                        "WS Service: Could not connect: {$e->getMessage()}",
                        [
                            'e' => $e
                        ]
                    );
                }
            );
    }
    public function getDefaultWsConnector()
    {
        return \Ratchet\Client\connect(
            'ws://' . getenv('FINXLOG_WEBSOCKET_HOST')
            . ':' . getenv('FINXLOG_WEBSOCKET_PORT')
            . getenv('FINXLOG_WEBSOCKET_SERVICE_PATH'),
            ['protocol1', 'subprotocol2'],
            ['Origin' => 'http://localhost']
        );
    }


    public function getModelQuotation()
    {
        if (!$this->model_quotation) {
            $this->model_quotation = new Model\QuotationAgg();
        }

        return $this->model_quotation;
    }

    public function getDefaultQueueConnector()
    {
        return new Connector\Queue(
            getenv('FINXLOG_AMQP_TUBE_WS')
        );
    }

    public function getFailQueueConnector()
    {
        //ignore
        return $this;
    }
    public function failQueu(Job $queue_job)
    {
        //ignore
        return $this;
    }


}