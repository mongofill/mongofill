<?php

namespace Mongofill {

use MongoConnectionException;
use MongoCursorException;

class Socket
{
    private $socket;
    private $host;
    private $port;

    private static $lastRequestId = 3;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function connect()
    {
        if ($this->socket) {
            return true;
        }

        $this->createSocket();
        $this->connectSocket();
    }

    protected function createSocket()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$this->socket) {
            throw new MongoConnectionException(sprintf(
                'error creating socket: %s',
                socket_strerror(socket_last_error())
            ));
        } 
    }

    protected function connectSocket()
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

    public function disconnect()
    {
        if (null !== $this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    public function putReadMessage($opCode, $opData)
    {
        $requestId = $this->getNextLastRequestId();
        $payload = $this->packMessage($requestId, $opCode, $opData);

        $this->putMessage($payload);

        return $this->getMessage($requestId);
    }

    public function putWriteMessage($opCode, $opData, array $options)
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
            $response = $this->getMessage($requestId);
            return $response['result'][0];
        }

        return true;
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

        return pack('Va*VVa*', 0, "admin.\$cmd\0", 0, -1, Bson::encode($command));
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

    protected function getMessage($requestId)
    {
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

        $offset = 0;
        $vars = Util::unpack(
            'Vflags/V2cursorId/VstartingFrom/VnumberReturned',
            $data,
            $offset,
            20
        );

        $documents = [];
        for ($i = 0; $i < $vars['numberReturned']; $i++) {
            $document = Bson::decDocument($data, $offset);
            $this->throwExceptionIfError($document);

            $documents[] = $document;
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
        $data = '';
        socket_recv($this->socket, $data, $length, MSG_WAITALL);
        if (false === $data) {
            // TODO handle read errors
            throw new \RuntimeException('unhandled socket read error');
        }

        return $data;
    }
}

}
