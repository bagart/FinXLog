<?php
namespace FinXLog\Module\ClientQueue;
use FinXLog\Exception\WrongParams;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\ImportQuotation;
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

    /**
     * @return Iface\WsConnector
     * @throws WrongParams
     */
    public function getWsConnector()
    {
        if (!$this->ws_connector) {
            $this->ws_connector = $this->getDefaultWsConnector();
        }

        return $this->ws_connector;
    }

    public function getDefaultWsConnector()
    {
        return (new Connector\RatchetClient)
            ->setUrl($this->getWsUrl());
    }

    public function addWsMessage($message)
    {
        $this->getWsConnector()->send($message);

        return $this;
    }

    public function getWsUrl()
    {
        if (empty(getenv('FINXLOG_WEBSOCKET_LISTEN_PORT'))) {
            throw new WrongParams('empty: FINXLOG_WEBSOCKET_LISTEN_PORT');
        }
        $host = (
            getenv('FINXLOG_WEBSOCKET_INTERNAL_HOST')
                ? getenv('FINXLOG_WEBSOCKET_INTERNAL_HOST')
                : (
            getenv('FINXLOG_WEBSOCKET_LISTEN_INTERFACE')
                ? getenv('FINXLOG_WEBSOCKET_LISTEN_INTERFACE')
                : '127.0.0.1'
            )
        );

        return "ws://$host"
            . (
            getenv('FINXLOG_WEBSOCKET_LISTEN_PORT')
                ? ':' . getenv('FINXLOG_WEBSOCKET_LISTEN_PORT')
                : null
            )
            . getenv('FINXLOG_WEBSOCKET_SERVICE_PATH');

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