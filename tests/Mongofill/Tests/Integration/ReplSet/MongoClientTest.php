<?php

namespace Mongofill\Tests\Integration\ReplSet;

use MongoClient;

/**
 * @group replset
 */
class MongoClientTest extends TestCase
{

    /**
     * Make sure we do not get arbiters/hidden members back
     */
    public function testGetHostsForReplSet()
    {
        $m = $this->getTestClient();

        $hosts = $m->getHosts();
        $this->assertCount(3, $hosts);

        $this->assertArrayHasKey(static::$primary_server, $hosts);
        $this->assertArrayHasKey(static::$secondary_server, $hosts);
        $this->assertArrayHasKey(static::$secondary_tagged_server, $hosts);

        $this->assertCount(7, $hosts[static::$primary_server]);
        $this->assertCount(7, $hosts[static::$secondary_server]);
        $this->assertCount(7, $hosts[static::$secondary_tagged_server]);

        $host = $hosts[static::$primary_server];
        $this->assertEquals(static::$primary_host, $host['host']);
        $this->assertEquals(static::$primary_port, $host['port']);
        $this->assertEquals(static::$primary_server, $host['hash']);
        $this->assertEquals(1, $host['health']);
        $this->assertGreaterThan(0, $host['lastPing']);

        $host = $hosts[static::$secondary_server];
        $this->assertEquals(static::$secondary_host, $host['host']);
        $this->assertEquals(static::$secondary_port, $host['port']);
        $this->assertEquals(static::$secondary_server, $host['hash']);
        $this->assertEquals(1, $host['health']);
        $this->assertGreaterThan(0, $host['lastPing']);

        $host = $hosts[static::$secondary_tagged_server];
        $this->assertEquals(static::$secondary_tagged_host, $host['host']);
        $this->assertEquals(static::$secondary_tagged_port, $host['port']);
        $this->assertEquals(static::$secondary_tagged_server, $host['hash']);
        $this->assertEquals(1, $host['health']);
        $this->assertGreaterThan(0, $host['lastPing']);
    }

    /**
     * @expectedException MongoConnectionException
     * @expectedExceptionMessageRegExp /replset/
     */
    public function testInvalidReplSetName()
    {
        $m = $this->getTestClient(['replicaSet' => 'invalid_replset']);
    }

    public function testWriteProtoclIsPrimary()
    {
        $m = $this->getTestClient();
        $protocol = $m->_getWriteProtocol();
        $this->assertEquals(static::$primary_server, $protocol->getServerHash());
    }

    public function testReadPreferencePrimary()
    {
        $m = $this->getTestClient(['readPreference' => MongoClient::RP_PRIMARY]);
        $protocol = $m->_getReadProtocol();
        $this->assertEquals(static::$primary_server, $protocol->getServerHash());
    }

    public function testReadPreferencePrimaryPreferred()
    {
        $m = $this->getTestClient(['readPreference' => MongoClient::RP_PRIMARY_PREFERRED]);
        $protocol = $m->_getReadProtocol();
        $this->assertEquals(static::$primary_server, $protocol->getServerHash());
    }

    public function testReadPreferenceSecondary()
    {
        $m = $this->getTestClient(['readPreference' => MongoClient::RP_SECONDARY]);
        $protocol = $m->_getReadProtocol();
        $this->assertThat(
            $protocol->getServerHash(),
            $this->logicalOr(
                new \PHPUnit_Framework_Constraint_IsEqual(static::$secondary_server),
                new \PHPUnit_Framework_Constraint_IsEqual(static::$secondary_tagged_server)
            ),
            "Server '{$protocol->getServerHash()}' is not a valid (non-hidden, non-arbiter) secondary server"
        );
    }

    public function testReadPreferenceSecondaryPreferred()
    {
        $m = $this->getTestClient(['readPreference' => MongoClient::RP_SECONDARY_PREFERRED]);
        $protocol = $m->_getReadProtocol();
        $this->assertThat(
            $protocol->getServerHash(),
            $this->logicalOr(
                new \PHPUnit_Framework_Constraint_IsEqual(static::$secondary_server),
                new \PHPUnit_Framework_Constraint_IsEqual(static::$secondary_tagged_server)
            ),
            "Server '{$protocol->getServerHash()}' is not a valid (non-hidden, non-arbiter) secondary server"
        );
    }

    public function testReadPreferenceTagged()
    {
        // Note: This test requires that the SECONDARY_TAGGED member of the set is tagged with {tag-test: tagged}
        //       which is automatically created if using the Travis environment setup script in tests/bin
        //       TODO: Adjust the env script to create SECONDARY_ONE and SECONDARY_TWO and do the tags as
        //             part of the test code itself with rs.reconfig() so it's more dynamic and makes the
        //             environment setup less complicated if done manually.

        $m = $this->getTestClient([
            'readPreference' => MongoClient::RP_SECONDARY,
            'readPreferenceTags' => [['tag-test' => 'tagged']]
        ]);

        $protocol = $m->_getReadProtocol();
        $this->assertEquals(static::$secondary_tagged_server, $protocol->getServerHash());
    }

    public function testReadPreferenceNearest()
    {
        $m = new MongoClient(static::$replset_conn_str, [
            'replicaSet' => static::REPLSET_NAME,
            'readPreference' => MongoClient::RP_NEAREST
        ]);
        $m->getHosts(); // Updates the pings internally for all hosts
        $reflectedClient = new \ReflectionClass($m);
        $property = $reflectedClient->getProperty('hosts');
        $property->setAccessible(true);
        $hosts = $property->getValue($m);
        $nearest_server = static::$secondary_server;
        foreach($hosts as $server => &$info) {
            if ($server === $nearest_server) {
                $info['ping'] = 1;
            } else {
                $info['ping'] = 2;
            }
        }
        $property->setValue($m, $hosts);
        $protocol = $m->_getReadProtocol();
        $this->assertEquals($nearest_server, $protocol->getServerHash());
    }

    /**
     * @expectedException MongoConnectionException
     * @expectedExceptionRegExp /tagsets/
     */
    public function testReadPreferenceNeverGivesHidden()
    {
        // Note: This test requires that the hidden member of the set is tagged with {tag-test: hidden}
        $m = $this->getTestclient([
            'readPreference' => MongoClient::RP_SECONDARY,
            'readPreferenceTags' => [['tag-test' => 'hidden']]
        ]);
        $protocol = $m->_getReadProtocol();
    }
}
