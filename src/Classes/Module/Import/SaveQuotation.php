<?php
namespace FinXLog\Module\Import;

use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Traits;
use FinXLog\Helper;
use FinXLog\Model;

/**
 * Class SaveQuotation
 * @package FinXLog\Module\Import
 */
class SaveQuotation extends AbsQuotation
{
    public function run($limit = null)
    {
        $this->getQueueConnector()
            ->watch();

        while ($limit === null || --$limit >= 0) {
            $queu_job = $this->getQueueConnector()->reserve();
            try {
                $this->importQuotation($queu_job->getData());
                $this->getQueueConnector()
                    ->delete($queu_job);
            } catch (\Exception $e) {
                $this->failQueu($queu_job);
            }
        }

        return $this;
    }
}