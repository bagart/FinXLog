<?php
namespace FinXLog\Module\Connector;

use FinXLog\Iface;
use FinXLog\Traits;

/**
 * Class Db
 * @package FinXLog\Module\Connector
 */
class Elastica implements Iface\Connector
{
    use Traits\WithConnectorRaw;
    protected $param = [];

    public function setParam(array $params = [])
    {
        $this->params = $params;

        return $this;
    }

    public function getDefaultConnector(array $params = [])
    {
        assert(!empty(getenv('FINXLOG_ELASTICA_PARAM')));
        assert(!empty(json_decode(getenv('FINXLOG_ELASTICA_PARAM'))));

        return new \Elastica\Client(
            $this->param + json_decode(getenv('FINXLOG_ELASTICA_PARAM'), true)
        );
    }
}