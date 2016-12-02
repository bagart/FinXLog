<?php

namespace FinXLog\Iface;

use FinXLog\Module\Connector;
use Pheanstalk\Job;

interface ModuleImportQueue extends ModuleImport
{
    /**
     * @return Connector\Queue
     */
    public function getFailQueueConnector();

    /**
     * @return Connector\Queue
     */
    public function getDefaultQueueConnector();

    /**
     * @param Job $queue_job
     * @return $this
     */
    public function failQueu(Job $queue_job);

    /**
     * @param string $string
     * @return $this
     */
    public function importQuotation($string);
}