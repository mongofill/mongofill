<?php

namespace Mongofill;

use MongoConnectionException;
use MongoCursorException;
use MongoCursorTimeoutException;

class Socket
{
    private $socket;
    private $host;
    private $port;
    private $connectTimeoutMS;

    private static $lastRequestId = 3;

    public function __construct($host, $port, $connectTimeoutMS = 60000)
    {
        $this->host = $host;
        $this->port = $port;
        $this->connectTimeoutMS = $connectTimeoutMS;
    }

    public function connect()
    {
        if ($this->socket) {
            return true;
        }

        $this->connectSocket();
    }

    protected function connectSocket()
    {
        if (filter_var($this->host, FILTER_VALIDATE_IP)) {
            $ip = $this->host;
        } else {
            $ip = gethostbyname($this->host);
            if ($ip == $this->host) {
                throw new MongoConnectionException(sprintf(
                    'couldn\'t get host info for %s',
                    $this->host
                ));
            }
        }

        // For some reason, PHP doesn't currently allow resources opened with
        // fsockopen/pfsockopen to be used with socket_* functions, but HHVM does.
        if (defined('HHVM_VERSION')) {
            $this->socket = pfsockopen($ip, $this->port, $errno, $errstr, $this->connectTimeoutMS / 1000);
            if ($this->socket === false) {
                $this->socket = null;
                throw new MongoConnectionException(sprintf(
                    "unable to connect to $ip:$this->port because: %s",
                    "$errstr ($errno)"
                ));
            }
        } else {
            $this->socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname("tcp"));
            if (!$this->socket) {
                throw new MongoConnectionException(sprintf(
                    'error creating socket: %s',
                    socket_strerror(socket_last_error())
                ));
            }
            $connected = socket_connect($this->socket, $ip, $this->port);
            if (!$connected) {
                throw new MongoConnectionException(sprintf(
                    "unable to connect to $ip:$this->port because: %s",
                    socket_strerror(socket_last_error())
                ));
            }
        }
    }

    public function disconnect()
    {
        if (null !== $this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    public function putReadMessage($opCode, $opData, $timeout)
    {
        $requestId = $this->getNextLastRequestId();
        $payload = $this->packMessage($requestId, $opCode, $opData);

        $this->putMessage($payload);

        return $this->getMessage($requestId, $timeout);
    }

    public function putWriteMessage($opCode, $opData, array $options, $timeout)
    {
        $requestId = $this->getNextLastRequestId();
        $payload = $this->packMessage($requestId, $opCode, $opData);

        $lastError = $this->createLastErrorMessage($options);
        if ($lastError) {
            $requestId = $this->getNextLastRequestId();
            $payload .= $this->packMessage($requestId, Protocol::OP_QUERY, $lastError);
        }

        $this->putMessage($payload);

        if ($lastError) {
            $response = $this->getMessage($requestId, $timeout);
            return $response['result'][0];
        }

        return true;
    }

    public function getServerHash()
    {
        return "$this->host:$this->port";
    }

    protected function throwExceptionIfError(array $record)
    {
        if (!empty($record['err'])) {
            throw new MongoCursorException($record['err'], $record['code']);
        }
    }

    protected function getNextLastRequestId()
    {
        return self::$lastRequestId++;
    }

    protected function createLastErrorMessage(array $options)
    {
        $command = array_merge(['getLastError' => 1], $options);

        if (!isset($command['w'])) {
            $command['w'] = 1;
        }

        if (!isset($command['j'])) {
            $command['j'] = false;
        }

        if (!isset($command['wtimeout'])) {
            $command['wtimeout'] = 10000;
        }

        if ($command['w'] === 0 && $command['j'] === false) {
            return;
        }

        return pack('Va*VVa*', 0, "admin.\$cmd\0", 0, -1, bson_encode($command));
    }

    protected function packMessage($requestId, $opCode, $opData, $responseTo = 0xffffffff)
    {
        $bytes = strlen($opData) + Protocol::MSG_HEADER_SIZE;

        return pack('V4', $bytes, $requestId, $responseTo, $opCode) . $opData;
    }

    protected function putMessage($payload)
    {
        $bytesSent = 0;
        $bytes = strlen($payload);

        do {
            $result = socket_write($this->socket, $payload);
            if (false === $result) {
                throw new \RuntimeException('unhandled socket write error');
            }

            $bytesSent += $result;
            $payload = substr($payload, $bytesSent);
        } while ($bytesSent < $bytes);
    }

    protected function getMessage($requestId, $timeout)
    {
        $this->setTimeout($timeout);
        $header = $this->readHeaderFromSocket();
        if ($requestId != $header['responseTo']) {
            throw new \RuntimeException(sprintf(
                'request/cursor mismatch: %d vs %d',
                $requestId,
                $header['responseTo']
            ));
        }

        $data = $this->readFromSocket(
            $header['messageLength'] - Protocol::MSG_HEADER_SIZE
        );

        $header = substr($data, 0, 20);
        $vars = unpack('Vflags/V2cursorId/VstartingFrom/VnumberReturned', $header);

        $documents = bson_decode_multiple(substr($data, 20));
        if ($documents) {
            $this->throwExceptionIfError($documents[0]);
        } else {
            $documents = [];
        }

        return [
            'result'   => $documents,
            'cursorId' => Util::decodeInt64($vars['cursorId1'], $vars['cursorId2']) ?: null,
            'start'    => $vars['startingFrom'],
            'count'    => $vars['numberReturned'],
        ];
    }

    protected function readHeaderFromSocket()
    {
        $data = $this->readFromSocket(Protocol::MSG_HEADER_SIZE);
        $header = unpack('VmessageLength/VrequestId/VresponseTo/Vopcode', $data);

        return $header;
    }

    protected function readFromSocket($length)
    {
        $data = null;
        @socket_recv($this->socket, $data, $length, MSG_WAITALL);
        if (null === $data) {
            $errorMsg = $this->handleSocketReadError();
            throw new \RuntimeException('unhandled socket read error: ' . $errorMsg);
        }

        return $data;
    }

    protected function handleSocketReadError()
    {
        $errno = socket_last_error($this->socket);
        if ($errno === 11 || $errno === 35) {
            throw new MongoCursorTimeoutException('Timed out waiting for data');
        }

        socket_clear_error($this->socket);
        return socket_strerror($errno);
    }

    protected function setTimeout($timeoutInMs)
    {
        $secs = $timeoutInMs / 1000;
        $mili = $timeoutInMs % 1000;

        if (defined('HHVM_VERSION')) {
            socket_set_timeout($this->socket, (int) $secs, (int) $mili);
        } else {
            $timeout = ['sec' => (int) $secs, 'usec' => (int) $mili * 1000];
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
        }
    }
};
