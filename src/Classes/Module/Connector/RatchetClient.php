<?php
namespace FinXLog\Module\Connector;

use FinXLog\Exception\WrongParams;
use FinXLog\Iface;
use FinXLog\Module\Logger;
use FinXLog\Traits;

/**
 * @todo + WAMP + ZMQ http://socketo.me/docs/push + http://autobahn.ws/js/tutorial.html
  * Class RatchetClient
 * @package FinXLog\Module\Connector
 */
class RatchetClient implements Iface\WsConnector
{
    use Traits\WithConnectorRaw;
    private $url;
    /**
     * @todo EventLib best, but not tested
     * @var \React\EventLoop\StreamSelectLoop|\React\EventLoop\LoopInterface
     */
    private $event_loop;
    /**
     * @var \React\Promise\PromiseInterface
     */
    private $promise;

    private $headers = [
        'Origin' => 'http://localhost'
    ];
    private $subProtocols = [];

    /**
     * BEFORE working with connector
     * @param $url
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    protected function getEventLoop()
    {
        if (!$this->event_loop) {
            $this->event_loop = \React\EventLoop\Factory::create();
        }

        return $this->event_loop;
    }

    protected function getPromise()
    {
        if (empty($this->url)) {
            throw new WrongParams(__CLASS__ . 'use setUrl first');
        }

        if (!$this->promise) {
            $connector = $this->getConnector();
            $this->promise = $connector(
                $this->url,
                $this->subProtocols,
                $this->headers
            );
        }

        return $this->promise;
    }

    /**
     * real work with $this->promise->then!
     * @return \Ratchet\Client\Connector
     */
    public function getDefaultConnector()
    {
        return new \Ratchet\Client\Connector($this->getEventLoop());
    }

    public function close()
    {
        if ($this->event_loop) {
            $this->event_loop->stop();
        }

        return $this;
    }

    public function reconnect()
    {

        $this->event_loop = null;
        $this->connector = null;
        $this->promise = null;
        Logger::dbg(':WS_RECONNECT:');

        return $this;
    }

    public function send($message, callable $incomingCallback = null)
    {
        $ratchet_module = $this;

        $is_first = empty($this->event_loop);
        $event_loop = $this->getEventLoop();

        $this->getPromise()->then(
            function(\Ratchet\Client\WebSocket $conn)
            use ($message, $event_loop, $incomingCallback, $ratchet_module)
            {
                $conn->on('error', function($error) use ($conn, $ratchet_module, $message, $incomingCallback) {
                    //@todo reconnect it's check only on second
                    //without send - 3 iterations
                    Logger::log()->notice("AMQP2WS(service): error");
                    $ratchet_module->reconnect();
                    if ($incomingCallback) {
                        $ratchet_module->send($message, $incomingCallback);
                    } else {
                        $ratchet_module->send($message);
                    }
                });

                if (!is_string($message)) {
                    $message = json_encode(
                        $message,
                        getenv('FINXLOG_DEBUG')
                            ? JSON_PRETTY_PRINT
                            : null
                    );
                }
                if (getenv('FINXLOG_DEBUG')) {
                    Logger::log()->info(
                        "AMQP2WS(service) send try:\t" . (
                            getenv('FINXLOG_DEBUG') == \Monolog\Logger::DEBUG
                                ? preg_replace('~\s+~u', ' ', $message) . '!!!!!'
                                : substr($message, 0, 70) . '...'
                        )
                    );
                    if (empty($conn->listeners('close'))) { //new connect
                        $conn->on(
                            'close',
                            function ($code = null, $reason = null)
                            use($incomingCallback) {
                                Logger::log()->info(
                                    "AMQP2WS(service) closed: ({$code} - {$reason})"
                                );
                                if ($incomingCallback) {
                                    Logger::log()->info(
                                        "AMQP2WS(service) incomingCallback: start"
                                    );
                                    $incomingCallback();
                                    Logger::log()->info(
                                        "AMQP2WS(service) incomingCallback: end"
                                    );
                                }
                            });
                        //@todo check that the socket stream is empty(released) without on:event
                        $conn->on('message', function ($message) use ($conn) {
                            Logger::log()->warning(
                                "AMQP2WS(service) ignore message: {$message}"
                            );
                        });
                    }
                }
                $conn->send($message);
                //exit from EventLoop
                $event_loop->stop();



            },
            function (\Throwable $e)
            use ($event_loop, $message, $incomingCallback)
            {
                Logger::error(
                    "WS Service error: Could not connect: {$e->getMessage()}",
                    [
                        'e' => $e,
                        'incomingCallback' => $incomingCallback,
                    ]
                );
                $event_loop->stop();
            }
        );
        if ($is_first) {
            $event_loop->run();
        }

        //push all queue
        $event_loop->tick();

        return $this;
    }

}