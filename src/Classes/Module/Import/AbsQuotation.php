<?php
namespace FinXLog\Module\Import;
use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Module\Import;
use FinXLog\Traits;
use FinXLog\Model;
use Pheanstalk\Job;

/**
 * make layer for different import source
 * Class SaveQuotation
 * @package FinXLog\Module\Import
 */
abstract class AbsQuotation implements Iface\ModuleImportQueue
{
    use Traits\WithQueueConnector;

    private $fail_queue_connector = null;
    private $model_quotation = null;

    public function getFailQueueConnector()
    {
        if (!$this->fail_queue_connector) {
            $this->fail_queue_connector = new Connector\Queue(
                getenv('FINXLOG_AMQP_TUBE_QUOTATION_FAIL')
            );
        }

        return $this->fail_queue_connector;
    }

    public function getModelQuotation()
    {
        if (!$this->model_quotation) {
            $this->model_quotation = new Model\Quotation();
        }

        return $this->model_quotation;
    }

    public function getDefaultQueueConnector()
    {
        return new Connector\Queue(
            getenv('FINXLOG_AMQP_TUBE_QUOTATION')
        );
    }


    public function failQueu(Job $queue_job)
    {
        if ($this->getFailQueueConnector()) {
            $this->getFailQueueConnector()
                ->put(
                    $queue_job->getData()
                );
        }

        if ($this->getQueueConnector()) {
            $this->getQueueConnector()
                ->delete($queue_job);
        }

        return $this;
    }

    public function importQuotation($string)
    {
        $this->getModelQuotation()
            ->save(
                Import\Source\Telnet::getFromRaw($string)
            );

        return $this;
    }

}