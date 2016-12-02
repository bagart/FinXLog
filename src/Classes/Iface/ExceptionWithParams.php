<?php
namespace FinXLog\Iface;

interface ExceptionWithParams extends FinXLogException
{
    /**
     * @return array
     */
    public function getParams();

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params);

    /**
     * @param array $params
     * @return $this
     */
    public function addParams(array $params);
}