<?php
namespace FinXLog\Module\ClientQueue;

use Elastica\Document;
use FinXLog\Exception;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\Logger;
use FinXLog\Traits;
use FinXLog\Model;
use Pheanstalk\Job;

class SearchQuotation extends AbsQuotation
{
    const QUOTATION_ALL = '_ALL';

    public function makeJob(Job $queue_job)
    {
        $job = json_decode($queue_job->getData(), true)
            + [
                'agg_type' => null,
                'agg_period' => null,
            ];

        if (!is_array($job) || empty($job['quotation'])) {
            throw new Exception\WrongParams('!job with quotation');
        }

        switch ($job['agg_type']) {
            case 'agg':
            case 'full':
                $quotations = $this->getModelQuotation()
                    ->getAgg($job['quotation'], $job['agg_period']);
                break;
            case 'doji':
            case 'AAPL':
                $quotations = $this->getModelQuotation()
                    ->getAAPL($job['quotation'], $job['agg_period']);
                break;
            default:
                $quotations = $this->getModelQuotation()
                    ->getQuotations(
                        $job['quotation'] == static::QUOTATION_ALL
                            ? null
                            : $job['quotation']
                    );
                foreach ($quotations as $key => $value) {
                    $quotations[$key] = $value->getData();
                }
                break;
        }

        $this->addWsMessage(
            [
                'type' => 'send',
                'quotations' => $quotations,
            ]
            + $job
        );

        return $this;
    }

    public function run()
    {
        $this->getQueueConnector()
            ->watch();
        //$this->getWsConnector()->send(['type' => 'test']);

        while (true) {
            Logger::dbg(':amqp_wait:');
            $queue_job = $this->getQueueConnector()->reserve();
            Logger::dbg(':amqp_make:');
            try {
                $this->makeJob($queue_job);
                Logger::log()->debug('+');
            } catch (\Throwable $e) {
                Logger::log()->debug('-');
                Logger::error("SaveQuotation exception: {$e->getMessage()}", ['e' => $e, 'job' => $queue_job]);
            }
            //try few times
            $this->getQueueConnector()
                ->delete($queue_job);
        }

        return $this;
    }
}