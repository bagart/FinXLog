<?php

$dot_env = new \Dotenv\Dotenv(__DIR__ .'/..');
$dot_env->load();
putenv('FINXLOG_ROOT_PATH=' . realpath(__DIR__ . '/..') . '/');

$dot_env->required('FINXLOG_QUOTATION_SERVER_ADDRESS')->notEmpty();
$dot_env->required('FINXLOG_QUOTATION_SERVER_PORT')->notEmpty();
$dot_env->required('FINXLOG_ELASTICO_PARAM')->notEmpty();

assert(json_decode(getenv('FINXLOG_ELASTICO_PARAM')));