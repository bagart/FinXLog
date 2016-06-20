<?php
namespace FinXLog\Module\Ratchet;
use FinXLog\Module\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\Version\RFC6455;

class Subscribers
{
    /**
     * @var ConnectionInterface[][][]
     */
    protected $subscribers = [];

    protected $map_by_conn = [];

    /**
     * @param array $message
     * @return string
     */
    public function getKeyByMessage($message)
    {
        return implode(';', array_filter([
            $message['quotation'],
            !empty($message['agg_period']) ? $message['agg_period'] : null
        ]));
    }

    /**
     * @param string|array $key
     * @return \Ratchet\ConnectionInterface[][]
     */
    public function get($key)
    {
        if (is_array($key)) {
            $key = $this->getKeyByMessage($key);
        }

        return empty($this->subscribers[$key])
            ? []
            : $this->subscribers[$key];
    }


    /**
     * @param RFC6455\Connection|ConnectionInterface $conn
     * @return array
     */
    public function getByConn(ConnectionInterface $conn)
    {
        if (empty($this->map_by_conn[$conn->resourceId])) {
            return [];
        }
        $result = [];
        foreach ($this->map_by_conn[$conn->resourceId] as $key) {
            if (empty($this->subscribers[$key][$conn->resourceId])) {
                Logger::error('subscribers: mapped key is missing');
            } else {
                $result[] = $this->subscribers[$key][$conn->resourceId];
            }
        }

        return $result;
    }

    /**
     * @param RFC6455\Connection|ConnectionInterface $conn
     * @param string $key
     * @return $this
     */
    public function drop(ConnectionInterface $conn, $key = null)
    {
        if ($key) {
            assert(isset($this->map_by_conn[$conn->resourceId][$key]));
            assert(isset($this->subscribers[$key][$conn->resourceId]));
            unset($this->map_by_conn[$conn->resourceId][$key]);
            unset($this->subscribers[$key][$conn->resourceId]);

            if (empty($this->subscribers[$key])) {
                unset($this->subscribers[$key]);
            }
        } elseif (isset($this->map_by_conn[$conn->resourceId])) {
            foreach ((array) $this->map_by_conn[$conn->resourceId] as $key) {
                unset($this->subscribers[$key][$conn->resourceId]);
                if (empty($this->subscribers[$key])) {
                    unset($this->subscribers[$key]);
                }
            }
            unset($this->map_by_conn[$conn->resourceId]);
        }

        return $this;
    }

    /**
     * @param string|array $message
     * @return $this
     */
    public function add(array $message)
    {
        $key = $this->getKeyByMessage($message);
        $this->subscribers[$key][$message['ws']->resourceId] = $message;
        $this->map_by_conn[$message['ws']->resourceId] = $key;

        return $this;
    }
}
