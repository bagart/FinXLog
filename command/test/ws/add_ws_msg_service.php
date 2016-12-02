#!/usr/bin/php
<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
$client = (new \FinXLog\Module\ClientQueue\SearchQuotation);

$client->addWsMessage('
     {
        "type":"send",
        "quotation":"_ALL",
        "quotations":[
            {"S":"BTCUSD","T":"2016/06/11 04:05:51","B":568.151},
            {"S":"TST","T":"2016/06/02 04:05:51","B":4}
         ]
     }
');

$client->addWsMessage('
     {
        "type":"send",
        "quotation":"BTCUSD",
        "quotations":[
            {"S":"BTCUSD","T":"2016/06/11 04:05:51","B":568.151},
            {"S":"BTCUSD","T":"2016/06/11 04:05:51","B":568.151}
         ]
     }
');

$client->addWsMessage('
     {
        "type":"send",
        "quotation":"BTCUSD",
        "agg_period":"M1",
        "agg_type":"AAPL",
        "AAPL":[
            {"S":"BTCUSD","T":"2016/06/11 04:05:51","B":568.151},
            {"S":"BTCUSD","T":"2016/06/11 04:05:51","B":568.151}
         ]
     }
');

$client->addWsMessage(['type' => 'conn_count']);
$client->addWsMessage(['type' => 'all', 'xx' => 123]);
$client->addWsMessage(['type' => 'test']);
