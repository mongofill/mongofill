<?php

use Mongofill\Protocol;

class MongoClient
{
    const VERSION = '1.3.0-mongofill';

    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 27017;
    const RP_PRIMARY   = 'primary';
    const RP_PRIMARY_PREFERRED = 'primaryPreferred';
    const PR_SECONDARY = 'secondary';
    const PR_SECONDARY_PREFERRED = 'secondaryPreferred';
    const PR_NEAREST   = 'nearest';

    public $connected  = false;
    public $status     = null;
    public $server     = null;
    public $persistent = null;

    private $options;
    private $host   = self::DEFAULT_HOST;
    private $port   = self::DEFAULT_PORT;

    /**
     * @var Protocol
     */
    private $protocol;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var array
     */
    private $databases = [];

    /**
     * @param string $server
     * @param null|array $options
     */
    function __construct($server = 'mongodb://localhost:27017', array $options = [])
    {
        if (!$options) {
            $options = ['connect' => true];
        }

        $this->options = $options;
        if (preg_match('/mongodb:\/\/([0-9a-zA-Z_.-]+)(:(\d+))?/', $server, $matches)) {
            $this->host = $matches[1];
            if (isset($matches[3])) $this->port = $matches[3];
        } else {
            $this->host = $server;
        }
        if (isset($options['port'])) $this->port = $options['port'];
        $this->server = "mongodb://{$this->host}:{$this->port}";

        if (isset($options['connect']) && $options['connect']) {
            $this->connect();
        }
    }

    /**
     * Connect to server
     * @return bool
     */
    public function connect()
    {
        if (!$this->socket) {
            $socket = fsockopen($this->host, $this->port);
            if (false === $socket) {
                return false;
            }
            $this->socket = $socket;
            $this->protocol = new Protocol($socket);
        }
        return true;
    }

    /**
     * Close opened server connection
     */
    public function close()
    {
        if (null !== $this->socket)
        {
            fclose($this->socket);
            $this->protocol = null;
        }
    }

    /**
     * @return Protocol
     */
    public function _getProtocol()
    {
        if (!$this->connected) {
            $this->connect();
        }

        return $this->protocol;
    }

    /**
     * @param string $name
     * @return MongoDB
     */
    public function selectDB($name)
    {
        if (!isset($this->databases[$name])) {
            $this->databases[$name] = new MongoDB($this, $name);
        }
        return $this->databases[$name];
    }

    /**
     * @param string $name
     * @return MongoDB
     */
    public function __get($name)
    {
        return $this->selectDB($name);
    }

    /**
     * @param string $db
     * @param string $collection
     * @return MongoCollection
     */
    public function selectCollection($db, $collection)
    {
        return $this->selectDB($db)->selectCollection($collection);
    }
}