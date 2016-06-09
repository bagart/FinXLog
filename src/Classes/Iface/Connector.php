<?php
namespace FinXLog\Iface;

interface Connector
{
    public function getDefaultConnector();
    public function setConnector($client);
    public function getConnector();
}