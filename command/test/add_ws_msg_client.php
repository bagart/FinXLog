#!/usr/bin/php
<?php
/**
 * native listener(simulate browser)
 * @usage: command/test/add_ws_msg_client.php [count]
 */
//
//by default - listen

require_once __DIR__ . '/../../vendor/autoload.php';

$listen_count = empty($argv[1]) ? 0 : $argv[1];

\Ratchet\Client\connect('ws://127.0.0.1:8080/ws')->then(function(\Ratchet\Client\WebSocket $conn) use ($listen_count) {
    $conn->on('message', function($msg) use ($conn, $listen_count) {
        echo "\nReceived: " . substr($msg, 0, 100). "...\n\n";
        if ($listen_count) {
            static $count = 0;
            if (++$count >= $listen_count) {
                $conn->close();
            }
        }

    });

    $conn->send('{"type": "quotations" }');
    $conn->send('{"type":"subscribe","quotation":"_ALL"}');
    $conn->send('{"type":"subscribe","quotation":"BTCUSD"}');
});