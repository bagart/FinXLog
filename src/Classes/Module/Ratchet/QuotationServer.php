<?php
namespace FinXLog\Module\Ratchet;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class QuotationServer
{
    /**
     * @var IoServer
     */
    private $io_server;

    public function getIoServer()
    {
        if (!$this->io_server) {
            assert(getenv('FINXLOG_WEBSOCKET_PORT') > 0);

            $this->io_server = IoServer::factory(
                new HttpServer(
                    new WsServer(
                        new QuotationWebSocketDelivery()
                    )
                ),
                getenv('FINXLOG_WEBSOCKET_PORT')
            );
        }

        return $this->io_server;
    }

    public function setIoServer(IoServer $io_server)
    {
        $this->io_server = $io_server;

        return $this;
    }

    public function run()
    {
        $this->getIoServer()->run();
    }
}
