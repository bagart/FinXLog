<?php

//memory economy
ini_set('xdebug.show_local_vars', 0);
ini_set('html_errors', (int) php_sapi_name() != 'cli');
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('track_errors', 1);
ini_set('track_errors', 1);

error_reporting(E_ALL | E_STRICT);

assert_options(ASSERT_ACTIVE,   true);
assert_options(ASSERT_BAIL,     true);
assert_options(ASSERT_WARNING,  true);
//ini_set('assert.exception', 1);

assert_options(
    ASSERT_CALLBACK,
    function ($string = null) {
        return trigger_error($string, E_WARNING);
    }
);

set_error_handler(
    function ($code, $string, $file, $line, $context = []) {
        \FinXLog\Module\Logger::error($string . ";code:$code at $file:$line", $context);

        return true;
    }
);

/*
assert(false,'assert');
trigger_error('trigger');
echo "\nend\n";
*/