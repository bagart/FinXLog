#!/usr/bin/php
<?php

require __DIR__ . '/../../vendor/autoload.php';

(new \FinXLog\Module\Ratchet\QuotationServer())->run();