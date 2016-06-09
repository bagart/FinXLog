<?php
namespace FinXLog\Iface;

use Pheanstalk;

interface QueueConnector extends Connector
{
    /**
     * @return  Pheanstalk\Pheanstalk
     */
    public function getConnector();
    /**
     * @param string $tube
     * @return $this
     */
    public function setTube($tube);

    /**
     * @return string|null
     */
    public function getTube();

    /**
     * @return $this
     */
    public function watch();

    /**
     * @return  Pheanstalk\Job|false
     */
    public function reserve();

    /**
     * @return Pheanstalk\Job $this
     */
    public function delete(Pheanstalk\Job $job);

    /**
     * @return $this
     */
    public function put($job);


}