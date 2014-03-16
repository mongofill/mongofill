<?php

namespace Mongofill {

class Protocol
{
    const OP_REPLY    = 1;
    const OP_MSG      = 1000;
    const OP_UPDATE   = 2001;
    const OP_INSERT   = 2002;
    const OP_QUERY    = 2004;
    const OP_GET_MORE = 2005;
    const OP_DELETE   = 2006;
    const OP_KILL_CURSORS = 2007;

    const QF_TAILABLE_CURSOR   = 2;
    const QF_SLAVE_OK          = 4;
    const QF_OPLOG_REPLAY      = 8;
    const QF_NO_CURSOR_TIMEOUT = 16;
    const QF_AWAIT_DATA        = 32;
    const QF_PARTIAL           = 64;

    const MSG_HEADER_SIZE = 16;

    private $socket;

    private static $lastRequestId = 3;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    protected function deliveryMessage($opCode, $opData)
    {
        $requestId = self::$lastRequestId++;
        $payload = $this->packMessage($requestId, $opCode, $opData);

        $this->sendMessage($payload);

        return $requestId;
    }

    protected function deliveryInsertMessage($opData, $lastError)
    {
        $requestId = self::$lastRequestId++;
        $payload = $this->packMessage($requestId, self::OP_INSERT, $opData, 0xffffffff);

        $requestId = self::$lastRequestId++;
        $payload .= $this->packMessage($requestId, self::OP_QUERY, $lastError, 0xffffffff);

        $this->sendMessage($payload);

        return $requestId;
    }

    private function sendMessage($payload)
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

    private function packMessage($requestId, $opCode, $opData, $responseTo = 0xffffffff)
    {
        $bytes = strlen($opData)+16;
    
        return pack('V4', $bytes, $requestId, $responseTo, $opCode) . $opData;
    }

    private function readFromSocket($length)
    {
        $data = '';
        socket_recv($this->socket, $data, $length, MSG_WAITALL);
        if (false === $data) {
            // TODO handle read errors
            throw new \RuntimeException('unhandled socket read error');
        }

        return $data;
    }

    public function opUpdate($fullCollectionName, array $query, array $update, array $options = [])
    {
        $flags = 0;
        if (!empty($options['upsert'])) $flags |= 1;
        if (!empty($options['multiple'])) $flags |= 2;
        $data = pack('Va*Va*a*',0, "$fullCollectionName\0", $flags, Bson::encode($query), Bson::encode($update));
        $this->deliveryMessage(self::OP_UPDATE, $data);
    }

    public function opInsert($fullCollectionName, array $documents, $continueOnError, $w = 1)
    {
        $flags = 0;
        $documentBsons = "";
        if ($continueOnError) $flags |= 1;

        foreach ($documents as $document) {
            $documentBsons .= Bson::encode($document);
        }

        $data = pack('Va*a*', $flags, "$fullCollectionName\0", $documentBsons);
        
        if ($w == 1) {
            $lastError = pack('Va*VVa*', 0, "admin.\$cmd\0", 0, -1, Bson::encode(['getLastError' => 1]));
            $requestId = $this->deliveryInsertMessage($data, $lastError);
            return $this->opReply($requestId);
        }

        $this->deliveryMessage(self::OP_INSERT, $data);
    }

    public function opQuery(
        $fullCollectionName, array $query, $numberToSkip, $numberToReturn, $flags, array $returnFieldsSelector = null)
    {
        // do request
        $data = pack('Va*VVa*', $flags, "$fullCollectionName\0", $numberToSkip, $numberToReturn, Bson::encode($query));
        if ($returnFieldsSelector) {
            $data .= Bson::encode($returnFieldsSelector);
        }
        $requestId = $this->deliveryMessage(self::OP_QUERY, $data);

        // get response
        return $this->opReply($requestId);
    }

    private function opReply($requestId)
    {
        $header = $this->readHeaderFromSocket();
        if ($requestId != $header['responseTo']) {
            throw new \RuntimeException(sprintf(
                'request/cursor mismatch: %d vs %d',
                $requestId,
                $header['responseTo']
            ));
        }

        $data = $this->readFromSocket($header['messageLength'] - self::MSG_HEADER_SIZE);

        // process response
        $offset = 0;
        $vars = Util::unpack(
            'Vflags/V2cursorId/VstartingFrom/VnumberReturned',
            $data,
            $offset,
            20
        );

        $documents = [];
        for ($i = 0; $i < $vars['numberReturned']; $i++) {
            $documents[] = Bson::decDocument($data, $offset);
        }

        return [
            'result'   => $documents,
            'cursorId' => Util::decodeInt64($vars['cursorId1'], $vars['cursorId2']) ?: null,
            'start'    => $vars['startingFrom'],
            'count'    => $vars['numberReturned'],
        ];
    }

    private function readHeaderFromSocket()
    {
        $data = $this->readFromSocket(self::MSG_HEADER_SIZE);
        $header = unpack('VmessageLength/VrequestId/VresponseTo/Vopcode', $data);

        return $header;
    }

    public function opGetMore($fullCollectionName, $limit, $cursorId)
    {
        // do request
        $data = pack('Va*Va8', 0, "$fullCollectionName\0", $limit, Util::encodeInt64($cursorId));
        $requestId = $this->deliveryMessage(self::OP_GET_MORE, $data);

        // get response
        return $this->opReply($requestId);
    }

    public function opDelete($fullCollectionName, array $query, array $options = [])
    {
        $flags = 0;

        // do request
        $data = pack('Va*Va*', 0, "$fullCollectionName\0", $flags,  Bson::encode($query));

        $requestId = $this->deliveryMessage(self::OP_DELETE, $data);
    }
}

}
