<?php
namespace FinXLog\Module\Connector;

use FinXLog\Iface;
use FinXLog\Traits;
use Pheanstalk\Job;
use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;

/**
 * Iface\Connector for BeanstalkD
 * Class Queue
 * @package FinXLog\Module\Connector
 */
class Queue implements Iface\QueueConnector
{
    use Traits\WithConnectorRaw;

    private $tube;
    private $ignore = 'default';
    private $encode = 'JSON';

    public function __construct($tube = null)
    {
        if ($tube !== null) {
            $this->setTube($tube);
        }
    }

    /**
     * @param string $tube
     * @return $this
     */
    public function setTube($tube)
    {
        assert($tube !== null);
        assert($this->tube === null, 'try to reconfigure');

        $this->tube = $tube;

        return $this;
    }

    public function getTube()
    {
        return $this->tube;
    }

    public function getDefaultConnector()
    {
        if (getenv('FINXLOG_AMQP_SERVER_PORT') && getenv('FINXLOG_AMQP_SERVER_ADDRESS')) {
            $connector = new Pheanstalk(
                getenv('FINXLOG_AMQP_SERVER_ADDRESS'),
                getenv('FINXLOG_AMQP_SERVER_PORT')
            );
        } else {
            $connector = new Pheanstalk(
                getenv('FINXLOG_AMQP_SERVER_ADDRESS') ?: '127.0.0.1'
            );
        }
        if ($this->tube) {
            $connector->useTube($this->tube);
        }

        return $connector;
    }

    protected function checkConnector(PheanstalkInterface $connector)
    {
        return $this;
    }

    public function watch()
    {
        $result = $this->getConnector()
            ->watch($this->getTube());
        if ($this->ignore) {
            $result->ignore($this->ignore);
        }

        return $this;
    }

    public function reserve()
    {
        return $this->getConnector()
            ->reserve();
    }

    public function delete(Job $job)
    {
        $this->getConnector()
            ->delete($job);

        return $this;
    }

    protected function encode($job)
    {
        switch ($this->encode) {
            case 'JSON':
                $job = json_encode($job);
                break;
            case 'php':
            case 'serialize':
                $job = serialize($job);
                break;
        }

        return $job;
    }


    protected function decode($job)
    {
        switch ($this->encode) {
            case 'JSON':
                $job = json_decode($job, true);
                break;
            case 'php':
            case 'serialize':
                $job = unserialize($job);
                break;
        }

        return $job;
    }

    public function put($job)
    {
        $this
            ->getConnector()
            ->put($this->encode($job));

        return $this;
    }
}