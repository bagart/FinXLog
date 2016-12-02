<?php
namespace FinXLog\Iface;

interface WsConnector extends Connector
{
    public function setUrl($url);
    public function send($message);
    public function close();
}