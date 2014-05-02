<?php

/**
 * A connection between PHP and MongoDB.
 *
 * This class extends MongoClient and provides access to several deprecated
 * methods.
 */
class Mongo extends MongoClient
{
    /**
     * Connects with a database server
     *
     * @return bool - If the connection was successful.
     */
    protected function connectUtil()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Get pool size for connection pools
     *
     * @return int - Returns the current pool size.
     */
    public static function getPoolSize()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Returns the address being used by this for slaveOkay reads
     *
     * @return string - The address of the secondary this connection is
     *   using for reads.   This returns NULL if this is not connected to a
     *   replica set or not yet initialized.
     */
    public function getSlave()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Get slaveOkay setting for this connection
     *
     * @return bool - Returns the value of slaveOkay for this instance.
     */
    public function getSlaveOkay()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Returns information about all connection pools.
     *
     * @return array - Each connection pool has an identifier, which starts
     *   with the host.
     */
    public function poolDebug()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Set the size for future connection pools.
     *
     * @param int $size - The max number of connections future pools will
     *   be able to create. Negative numbers mean that the pool will spawn an
     *   infinite number of connections.
     *
     * @return bool - Returns the former value of pool size.
     */
    public static function setPoolSize($size)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Change slaveOkay setting for this connection
     *
     * @param bool $ok - If reads should be sent to secondary members of a
     *   replica set for all possible queries using this MongoClient
     *   instance.
     *
     * @return bool - Returns the former value of slaveOkay for this
     *   instance.
     */
    public function setSlaveOkay($ok = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Choose a new secondary for slaveOkay reads
     *
     * @return string - The address of the secondary this connection is
     *   using for reads.
     */
    public function switchSlave()
    {
        throw new Exception('Not Implemented');
    }
}
