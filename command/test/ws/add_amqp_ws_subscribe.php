#!/usr/bin/php
<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

(new \FinXLog\Module\Connector\Queue(
        getenv('FINXLOG_AMQP_TUBE_WS')
))
    ->put([
        'quotation' => 'BTCUSD'
    ])
    ->put([
        'quotation' => 'BTCUSD',
        'agg_type' => 'AAPL',
        'agg_period' => 'M1',
    ]);