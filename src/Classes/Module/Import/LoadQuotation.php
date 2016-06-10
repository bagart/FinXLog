<?php
namespace FinXLog\Module\Import;

use FinXLog\Exception\ConnectionError;
use FinXLog\Exception\ErrorParam;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\Logger;
use FinXLog\Traits;
use FinXLog\Helper;
use FinXLog\Model;

/**
 * Class Quotation
 * @package FinXLog\Module\Import
 */
class LoadQuotation extends AbsQuotation
{
    use Traits\WithConnector;
    protected $work_with_amqp = 'auto';

    public function setWorkWithSwitch($work_with_amqp)
    {
        $this->work_with_amqp = $work_with_amqp;
        return $this;
    }

    public function getDefaultConnector()
    {
        return new Connector\Quotation();
    }

    public function run($limit = null)
    {
        while ($limit === null || --$limit >= 0) {
            $this->saveJob(
                $this->getConnector()
                    ->getQuotation()
            );
            Logger::log()->info('job done');
        }

        return $this;
    }

    public function saveJob($string)
    {
        if ($this->work_with_amqp) {
            if ($this->getQueueConnector()) {
                $this->getQueueConnector()
                    ->put($string);

                return $this;
            }
            if ($this->work_with_amqp != 'auto') {
                throw new ConnectionError('amqp');
            }
        }

        $this->importQuotation($string);

        return $this;
    }
}