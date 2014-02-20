<?php

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

    /** @var   */
    private $connection;

    function __construct($server = 'mongodb://localhost:27017', array $options = [ 'connect' => true ])
    {
        $this->options = $options;
        if (preg_match('/mongodb:\/\/([0-9a-zA-Z_.-]+)(:(\d+))?/', $server, $matches)) {
            $this->host = $matches[1];
            if (isset($matches[3])) $this->port = $matches[3];
        } else {
            $this->host = $server;
        }
        if (isset($options['port'])) $this->port = $options['port'];
        $this->server = "mongodb://{$this->host}:{$this->port}";
    }

    /**
     * Connect to server
     * @return bool
     */
    public function connect()
    {

    }






} 