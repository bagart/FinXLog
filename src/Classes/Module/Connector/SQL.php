<?php
namespace FinXLog\Module\Connector;

use FinXLog\Iface;
use FinXLog\Traits;

/**
 * Class Db
 * @package FinXLog\Module\Connector
 */
class SQL implements Iface\SQLConnector
{
    use Traits\WithConnectorRaw;

    public function getDefaultConnector()
    {
        $connector = new \PDO(
            getenv('FINXLOG_SQL_SERVER_DSN'),
            getenv('FINXLOG_SQL_SERVER_USER'),
            getenv('FINXLOG_SQL_SERVER_PASSWORD')
        );

        return $connector;
    }
}