#!/usr/bin/php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

var_export(
        (new \FinXLog\Model\QuotationAgg)
                ->getAAPL('BTCUSD', 'day')
);