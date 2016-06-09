<?php
namespace FinXLog\Iface;

interface RWConnector
{
    public function read();
    public function write($buffer);
}