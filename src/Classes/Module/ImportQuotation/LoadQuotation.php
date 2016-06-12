<?php
namespace FinXLog\Module\ImportQuotation;

use FinXLog\Exception\ConnectionError;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\Logger;
use FinXLog\Traits;
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
            $import = null;
            try {
                $import = $this->getConnector()
                    ->getQuotation();
            } catch (\Throwable $e) {
                Logger::log()->debug('-');
                Logger::error('LoadQuotation getQuotation error', $e);
            }

            if ($import) {
                try {
                    $this->saveJob($import);
                    Logger::log()->debug('+');
                } catch (\Exception $e) {
                    Logger::log()->debug('-');
                    Logger::error('LoadQuotation saveJob. lost: ' . var_export($import, true), $e);
                }
            }
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