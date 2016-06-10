<?php
namespace FinXLog\Iface;

interface Connector
{
    public function getDefaultConnector();
    public function setConnector($connector);
    public function getConnector();
}