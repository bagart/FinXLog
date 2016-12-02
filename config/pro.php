<?php

try {
    assert(\FinXLog\Module\Logger::log() instanceof \Monolog\Logger);
    $logClient = new \Elastica\Client(
        json_decode(
            getenv('FINXLOG_ELASTICA_LOG_PARAM')
                ? getenv('FINXLOG_ELASTICA_LOG_PARAM')
                : getenv('FINXLOG_ELASTICA_PARAM'),
            true
        )
    );

    assert(is_array($logClient->getStatus()->getIndexNames()));

    /**
     * log message in elasticsearch
     */
    \FinXLog\Module\Logger::log()
        ->pushHandler(
            new \Monolog\Handler\ElasticSearchHandler(
                $logClient,
                [],
                Monolog\Logger::INFO
            )
        );
} catch (\Exception $e) { }