#!/usr/bin/php
<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

(new FinXLog\Module\ClientQueue\SearchQuotation())
    ->run();
