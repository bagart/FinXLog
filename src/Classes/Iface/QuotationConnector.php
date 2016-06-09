<?php
namespace FinXLog\Iface;

interface QuotationConnector extends Connector, RWConnector
{
    public function getQuotation();
}