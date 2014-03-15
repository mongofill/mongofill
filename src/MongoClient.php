<?php

use Mongofill\Protocol;

/**
 * A connection manager for PHP and MongoDB.
 */
class MongoClient
{
    const VERSION = '1.3.0-mongofill';
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 27017;
    const RP_PRIMARY   = 'primary';
    const RP_PRIMARY_PREFERRED = 'primaryPreferred';
    const RP_SECONDARY = 'secondary';
    const RP_SECONDARY_PREFERRED = 'secondaryPreferred';
    const RP_NEAREST   = 'nearest';

    /**
     * @var boolean
     */
    public $connected;

    /**
     * @var string
     */
    public $boolean = false;

    /**
     * @var string
     */
    public $status;
    
    /**
     * @var string
     */
    public $server;
    
    /**
     * @var boolean
     */
    public $persistent;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $host = self::DEFAULT_HOST;

    /**
     * @var int
     */
    private $port = self::DEFAULT_PORT;

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
     * Creates a new database connection object
     *
     * @param string $server - The server name.
     * @param string $options - An array of options for the connection. 
     *
     * @return array - Returns the database response.
     */
    public function __construct($server = 'mongodb://localhost:27017', array $options = [])
    {
        if (!$options) {
            $options = ['connect' => true];
        }

        $this->options = $options;
        if (preg_match('/mongodb:\/\/([0-9a-zA-Z_.-]+)(:(\d+))?/', $server, $matches)) {
            $this->host = $matches[1];
            if (isset($matches[3])) {
                $this->port = $matches[3];
            }
        } else {
            $this->host = $server;
        }

        if (isset($options['port'])) {
            $this->port = $options['port'];
        }

        $this->server = "mongodb://{$this->host}:{$this->port}";

        if (isset($options['connect']) && $options['connect']) {
            $this->connect();
        }
    }

    /**
     * Gets a database
     *
     * @param string $dbname - The database name.
     *
     * @return MongoDB - Returns a new db object.
     */
    public function __get($dbname)
    {
        return $this->selectDB($dbname);
    }

    /**
     * Connects to a database server
     *
     * @return bool - If the connection was successful.
     */
    public function connect()
    {
        if ($this->socket) {
            return true;
        }

        $this->createSocket();
        $this->connectSocket();

        $this->protocol = new Protocol($this->socket);

        return true;
    }

    private function createSocket()
    {
        if (!$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new MongoConnectionException(sprintf(
                'error creating socket: %s',
                socket_strerror(socket_last_error())
            ));
        } 
    }

    private function connectSocket()
    {
        $ip = gethostbyname($this->host);
        if ($ip == $this->host) {
            throw new MongoConnectionException(sprintf(
                'couldn\'t get host info for %s',
                $this->host
            ));
        } 

        $connected = socket_connect($this->socket, $ip, $this->port);
        if (false === $connected) {
            throw new MongoConnectionException(sprintf(
                'unable to connect %s',
                socket_strerror(socket_last_error())
            ));
        } 
    }

    /**
     * Closes this connection
     *
     * @param boolean|string $connection - If connection is not given, or
     *   FALSE then connection that would be selected for writes would be
     *   closed. In a single-node configuration, that is then the whole
     *   connection, but if you are connected to a replica set, close() will
     *   only close the connection to the primary server.
     *
     * @return bool - Returns if the connection was successfully closed.
     */
    public function close($connection = null)
    {
        if (null !== $this->socket) {
            socket_close($this->socket);
            $this->protocol = null;
        }

        //TODO: implement $connection handling
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
     * Gets a database
     *
     * @param string $name - The database name.
     *
     * @return MongoDB - Returns a new database object.
     */
    public function selectDB($name)
    {
        if (!isset($this->databases[$name])) {
            $this->databases[$name] = new MongoDB($this, $name);
        }

        return $this->databases[$name];
    }

    /**
     * Gets a database collection
     *
     * @param string $db - The database name.
     * @param string $collection - The collection name.
     *
     * @return MongoCollection - Returns a new collection object.
     */
    public function selectCollection($db, $collection)
    {
        return $this->selectDB($db)->selectCollection($collection);
    }

    /**
     * Drops a database [deprecated]
     *
     * @param mixed $db - The database to drop. Can be a MongoDB object or
     *   the name of the database.
     *
     * @return array - Returns the database response.
     */
    public function dropDB($db)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Return info about all open connections
     *
     * @return array - An array of open connections.
     */
    public static function getConnections()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Updates status for all associated hosts
     *
     * @return array - Returns an array of information about the hosts in
     *   the set. Includes each host's hostname, its health (1 is healthy),
     *   its state (1 is primary, 2 is secondary, 0 is anything else), the
     *   amount of time it took to ping the server, and when the last ping
     *   occurred. For example, on a three-member replica set, it might look
     *   something like:
     */
    public function getHosts()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Get the read preference for this connection
     *
     * @return array -
     */
    public function getReadPreference()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Kills a specific cursor on the server
     *
     * @param string $serverHash - The server hash that has the cursor.
     *   This can be obtained through MongoCursor::info.
     * @param int|mongoint64 $id - The ID of the cursor to kill. You can
     *   either supply an int containing the 64 bit cursor ID, or an object
     *   of the MongoInt64 class. The latter is necessary on 32 bit platforms
     *   (and Windows).
     *
     * @return bool - Returns TRUE if the method attempted to kill a
     *   cursor, and FALSE if there was something wrong with the arguments
     *   (such as a wrong server_hash). The return status does not reflect
     *   where the cursor was actually killed as the server does not provide
     *   that information.
     */
    public function killCursor($serverHash, $id)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Lists all of the databases available.
     *
     * @return array - Returns an associative array containing three
     *   fields. The first field is databases, which in turn contains an
     *   array. Each element of the array is an associative array
     *   corresponding to a database, giving th database's name, size, and if
     *   it's empty. The other two fields are totalSize (in bytes) and ok,
     *   which is 1 if this method ran successfully.
     */
    public function listDBs()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Set the read preference for this connection
     *
     * @param string $readPreference -
     * @param array $tags -
     *
     * @return bool -
     */
    public function setReadPreference($readPreference, array $tags)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * String representation of this connection
     *
     * @return string - Returns hostname and port for this connection.
     */
    public function __toString()
    {
        return (string) $this->host . ':' . (string) $this->port;
    }
}
