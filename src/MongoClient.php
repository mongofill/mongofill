<?php

use Mongofill\Protocol;
use Mongofill\Socket;

/**
 * A connection manager for PHP and MongoDB.
 */
class MongoClient
{
    const VERSION = '1.3.0-mongofill';
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 27017;
    const DEFAULT_DATABASE = 'admin';
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
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var array
     */
    private $hosts;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $uri;

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
        if (!$server || strpos($server, 'mongodb://') != 0) {
            throw new MongoConnectionException('failed to get host or port from ' . $server);
        }

        $this->uri = $server;

        $uri = substr($server, 10);

        $serverPart = '';
        $nsPart = null;
        $optionsPart = '';

        {
            $idx = strrpos($uri, '/');

            if ($idx === false) {
                if (strpos($uri, '?') !== false) {
                    throw new MongoConnectionException('malformed uri: ' . $server);
                }

                $serverPart = $uri;
                $nsPart = null;
                $optionsPart = '';
            } else {
                $serverPart = substr($uri, 0, $idx);
                $nsPart = substr($uri, $idx + 1);

                $idx = strrpos($nsPart, '?');

                if ($idx !== false) {
                    $optionsPart = substr($nsPart, $idx + 1);
                    $nsPart = substr($nsPart, 0, $idx);
                } else {
                    $optionsPart = '';
                }
            }
        }

        {// username,password,hosts
            $idx = strrpos($serverPart, '@');

            if ($idx !== false) {
                $authPart = substr($serverPart, 0, $idx);
                $serverPart = substr($serverPart, $idx + 1);

                $idx = strrpos($authPart, ':');

                if ($idx === false) {
                    $this->username = urldecode($authPart);
                    $this->password = '';
                } else {
                    $this->username = urldecode(substr($authPart, 0, $idx));
                    $this->password = urldecode(substr($authPart, $idx + 1));
                }
            }

            if (strlen($serverPart) == 0) {
                throw new MongoConnectionException('malformed uri: ' . $server);
            }

            $this->hosts = explode(',', $serverPart);

            if (count($this->hosts) > 1) {
                throw new Exception('Not Implemented');
            }
        }

        if ($nsPart != null && strlen($nsPart) != 0) {// database
            $this->database = $nsPart;
        }

        $uri_options = [];
        $split_options = preg_split('/[&;]+/', $optionsPart);

        foreach ($split_options as $part) {
            $idx = strrpos($part, '=');

            if ($idx !== false) {
                $key = substr($part, 0, $idx);
                $value = substr($part, $idx + 1);

                $uri_options[$key] = $value;
            }
        }

        if (!$options) {
            $options = ['connect' => true];
        }

        $this->options = array_replace($uri_options, $options);

        // handle legacy settings
        if (array_key_exists('timeout', $this->options) && !array_key_exists('connectTimeoutMS', $this->options)) {
            $this->options['connectTimeoutMS'] = $this->options['timeout'];
            unset($this->options['timeout']);
        }
        if (array_key_exists('wtimeout', $this->options) && !array_key_exists('wtimeoutms', $this->options)) {
            $this->options['wtimeoutms'] = $this->options['wtimeout'];
            unset($this->options['wtimeout']);
        }

        if (array_key_exists('username', $this->options)) {
            $this->username = $this->options['username'];
        }

        if (array_key_exists('password', $this->options)) {
            $this->password = $this->options['password'];
        }

        if (array_key_exists('db', $this->options)) {
            $this->database = $this->options['db'];
        }

        if ($this->database == null && $this->username != null) {
            $this->database = self::DEFAULT_DATABASE;
        }

        $idx = strrpos($this->hosts[0], ':');

        if ($idx === false) {
            $this->host = $this->hosts[0];
        } else {
            $this->host = substr($this->hosts[0], 0, $idx);
            $this->port = substr($this->hosts[0], $idx + 1);
        }

        if (isset($options['port'])) {
            $this->port = $options['port'];
        }

        $this->socket = new Socket($this->host, $this->port);
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
        if ($this->protocol) {
            return true;
        }

        $this->socket->connect();
        $this->protocol = new Protocol($this->socket);

        if ($this->database != null) {
            $db = $this->selectDB($this->database);

            if ($this->username != null) {
                return $db->authenticate($this->username, $this->password);
            }
        }

        return true;
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
        if ($this->socket) {
            $this->socket->disconnect();
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
     * @return string - The name of the database to authenticate
     */
    public function _getAuthenticationDatabase()
    {
        return $this->database;
    }

    /**
     * @return string - The username for authentication
     */
    public function _getAuthenticationUsername()
    {
        return $this->username;
    }

    /**
     * @return string - The password for authentication
     */
    public function _getAuthenticationPassword()
    {
        return $this->password;
    }

    /**
     * @param string $name - The option name.
     *
     * @return string - The option value
     */
    public function _getOption($name)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return null;
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
        // since we currently support just single server connection,
        // the $serverHash arg is ignored

        if ($id instanceof MongoInt64) {
            $id = $id->value;
        } elseif (!is_numeric($id)) {
            return false;
        }

        $this->protocol->opKillCursors([ (int)$id ], [], MongoCursor::$timeout);

        return true;
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
    public function setReadPreference($readPreference, array $tags = null)
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
