<?php
namespace FinXLog\Traits;

use FinXLog\Iface;

/**
 * Class WithConnector
 * @package FinXLog\Traits
 */
trait WithQueueConnector
{
    /**
     * @var Iface\QueueConnector
     */
    private $queue_connector;

    abstract public function getDefaultQueueConnector();

    public function setQueueConnector(Iface\QueueConnector $queue_connector)
    {
        if (method_exists($this, 'checkQueueConnector')) {
            $this->checkQueueConnector($queue_connector);
        }
        $this->queue_connector = $queue_connector;

        return $this;
    }

    public function getQueueConnector()
    {
        if (!$this->queue_connector) {
            $this->setQueueConnector(
                $this->getDefaultQueueConnector()
            );
        }

        return $this->queue_connector;
    }
}