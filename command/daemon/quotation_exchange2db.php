#!/usr/bin/php
<?php

/**
 * Always on service: load quotation from source
 */
require_once __DIR__ . '/../../vendor/autoload.php';

$import = new FinXLog\Module\Import\LoadQuotation();

$import
    ->setWorkWithSwitch(false)
    ->run(
        !empty($argv[1])
            ? $argv[1]
            : null
    );
