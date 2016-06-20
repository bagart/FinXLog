<?php
namespace FinXLog\Module\ImportQuotation;

use FinXLog\Exception;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\Logger;
use FinXLog\Traits;
use FinXLog\Model;

/**
 * Class SaveQuotation
 * @package FinXLog\Module\Import
 */
class SaveQuotation extends AbsQuotation
{
    public function run($limit = null)
    {
        while ($limit === null || --$limit >= 0) {
            $queue_job = $this->getQueueConnector()->reserve();
            try {
                $this->importQuotation($queue_job->getData());
                Logger::log()->debug('+');
                $this->getQueueConnector()->delete($queue_job);
            } catch (Exception\WrongImport $e) {
                //Logger::log()->debug('-');
                Logger::error('SaveQuotation WrongImport', $e);
                $this->getQueueConnector()->delete($queue_job);
            } catch (\Throwable $e) {
                //Logger::log()->debug('-');
                Logger::error('SaveQuotation exception', $e);
                $this->failQueu($queue_job);
            }
        }

        return $this;
    }
}