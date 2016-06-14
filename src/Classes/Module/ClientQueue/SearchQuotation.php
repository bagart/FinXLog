<?php
namespace FinXLog\Module\ClientQueue;

use FinXLog\Exception;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\Logger;
use FinXLog\Traits;
use FinXLog\Model;
use Pheanstalk\Job;

/**
 * Class SaveQuotation
 * @package FinXLog\Module\Import
 */
class SearchQuotation extends AbsQuotation
{
    const QUOTATION_ALL = '_ALL';

    public function makeJob(Job $queue_job)
    {
        $job = json_decode($queue_job->getData(), true);

        if (!is_array($job) || empty($job['quotation'])) {
            throw new Exception\WrongParams('!job with quotation');
        }
        if (!empty($job['agg'])) {
            $this->addWsMessage(
                $this->getModelQuotation()
                    ->getDoji($job['quotation'], $job['agg_period'])
            );
        } else {
            $this->addWsMessage(
                $this->getModelQuotation()
                    ->getQuotations(
                        $job['quotation'] == static::QUOTATION_ALL
                            ? null
                            : $job['quotation']
                    )
            );
        }
        switch($job['agg']) {
            case static::QUOTATION_ALL:

                break;
            default:

                break;
        }

    }
    public function run()
    {
        $this->getQueueConnector()
            ->watch();

        while (true) {
            $queue_job = $this->getQueueConnector()->reserve();
            try {
                $this->makeJob($queue_job);
                Logger::log()->debug('+');
            } catch (\Throwable $e) {
                Logger::log()->debug('-');
                Logger::error('SaveQuotation exception', ['e' => $e, 'job' => $queue_job]);
            }
            //try few times
            $this->getQueueConnector()
                ->delete($queue_job);
        }

        return $this;
    }
}