<?php
namespace FinXLog\Module\Connector;

use FinXLog\Iface;
use FinXLog\Traits;

/**
 * Class Db
 * @package FinXLog\Module\Connector
 */
class Db implements Iface\Connector
{
    use Traits\WithConnectorRaw;

    public function getDefaultConnector()
    {
        //PDO
        $connector = new \PDO();
        return $connector;
    }

    //protected function checkConnector(\PDOStatement $connector)
    //{
    //    return $this;
    //}

    public function save()
    {
        $result = $this->getConnector()
            ->save();
        return $this;
    }
}