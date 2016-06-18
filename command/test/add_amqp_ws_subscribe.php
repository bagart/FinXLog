#!/usr/bin/php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

(new \FinXLog\Module\Connector\Queue(
        getenv('FINXLOG_AMQP_TUBE_WS')
))
    ->put([
        'quotation' => 'BTCUSD',
        'agg' => null,
        'agg_period' => null,
    ])
    ->put([
        'quotation' => 'BTCUSD',
        'agg' => 'doji',
        'agg_period' => key((new \FinXLog\Model\QuotationAgg)->getAggPeriod()),
    ]);