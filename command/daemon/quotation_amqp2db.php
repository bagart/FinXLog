#!/usr/bin/php
<?php

/**
 * Always on service: load quotation from source
 */
require_once __DIR__ . '/../../vendor/autoload.php';

$import = (new FinXLog\Module\Import\SaveQuotation());


if (in_array('fail', $argv)) {
    $import->setQueueConnector($import->getFailQueueConnector());
}
$count = null;
foreach ($argv as $a) {
    if (is_numeric($a) && $a > 0) {
        $count = $a;
    }
}

$import->run($count);
