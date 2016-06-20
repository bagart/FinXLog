<?php
namespace FinXLog\Exception;

use FinXLog\Iface;

class WrongParams extends \ErrorException implements Iface\ExceptionWithParams
{
    protected $params = [];

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }
    public function addParams(array $params)
    {
        $this->params = $params + $this->params;

        return $this;
    }
}