<?php
namespace FinXLog\Module\Import;

use FinXLog\Iface;
use FinXLog\Module\Connector;
use FinXLog\Traits;
use FinXLog\Helper;
use FinXLog\Model;
use Pheanstalk\Job;

/**
 * Class SaveQuotation
 * @package FinXLog\Module\Import
 */
abstract class AbsQuotation implements Iface\ModuleImport
{
    use Traits\WithQueueConnector;

    private $fail_queue_connector = null;
    private $model_quotation = null;

    abstract public function run($limit = null);

    public function getFailQueueConnector()
    {
        if ($this->fail_queue_connector) {
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

    public function getDefaultDbConnector()
    {
        return new Connector\Db();
    }

    public function getDefaultQueueConnector()
    {
        return new Connector\Queue(
            getenv('FINXLOG_AMQP_TUBE_QUOTATION')
        );
    }


    public function failQueu(Job $queu_job)
    {
        $this->getFailQueueConnector()->put($queu_job->getData());

        $this->getQueueConnector()
            ->delete($queu_job);
    }

    public function importQuotation($string)
    {
        $this->getModelQuotation()
            ->save(
                $this->getFromString($string)
            );
    }

    public function getFromString($string)
    {
        assert(is_string($string));

        $field = explode(
            ';',
            trim($string)
        );
        assert(count($field) == 3);
        $result = [];
        foreach ($field as $cur) {
            list($name, $value) = explode('=', $cur);
            $result[$name] = $value;
        }

        $result['T'] = strtotime($result['T']);

        return $result;
    }
}