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

    private function sendMessage($opCode, $opData, $responseTo = 0xffffffff)
    {
        $requestId = self::$lastRequestId++;
        $bytes = strlen($opData)+16;
        $bytesSent = 0;
        $payload = pack('V4', $bytes, $requestId, $responseTo, $opCode) . $opData;
        do {
            $result = fwrite($this->socket, $payload);
            if (false === $result) {
                // TODO handle write errors
                throw new \RuntimeException('unhandled socket write error');
            }
            $bytesSent += $result;
            $payload = substr($payload, $bytesSent);
        } while ($bytesSent < $bytes);

        return $requestId;
    }

    public function opUpdate($fullCollectionName, array $query, array $update, array $options = [])
    {
        $flags = 0;
        if (!empty($options['upsert'])) $flags |= 1;
        if (!empty($options['multiple'])) $flags |= 2;
        $data = pack('Va*Va*a*',0, "$fullCollectionName\0", $flags, Bson::encode($query), Bson::encode($update));
        $this->sendMessage( self::OP_UPDATE, $data);
    }

    public function opInsert($fullCollectionName, array $documents, $continueOnError)
    {
        $flags = 0;
        $documentBsons = "";
        if ($continueOnError) $flags |= 1;
        foreach($documents as $document) {
            $documentBsons .= Bson::encode($document);
        }
        $data = pack('Va*a*', $flags, "$fullCollectionName\0", $documentBsons);
        $this->sendMessage(self::OP_INSERT, $data);
    }

    public function opQuery(
        $fullCollectionName, array $query, $skip, $limit, $flags, array $returnFieldsSelector = null)
    {
        // do request
        $data = pack('Va*VVa*', $flags, "$fullCollectionName\0", $skip, $limit, Bson::encode($query));
        if ($returnFieldsSelector) {
            $data .= Bson::encode($returnFieldsSelector);
        }
        $requestId = $this->sendMessage(self::OP_QUERY, $data);

        // get response
        return $this->opReply($requestId);
    }

    private function opReply($requestId)
    {
        // read response
        $bytesReceived = 0;
        $bytesToRead = self::MSG_HEADER_SIZE;
        $data = '';
        $header = null;
        do {
            $data .= fread($this->socket, $bytesToRead);
            if (false === $data) {
                // TODO handle read errors
                throw new \RuntimeException('unhandled socket read error');
            }
            $bytesReceived += strlen($data);

            // load header first
            if (!$header && $bytesReceived >= $bytesToRead) {
                $header = $this->decodeHeader($data);
                $bytesToRead = $header['messageLength'] - self::MSG_HEADER_SIZE;
            }
        } while ($bytesReceived < $bytesToRead);

        // process response
        $offset = self::MSG_HEADER_SIZE;
        $vars = unpack('Vflags/V2cursorId/VstartingFrom/VnumberReturned', substr($data, $offset, 20));
        $offset += 20;
        $documents = [];
        for($i = 0; $i < $vars['numberReturned']; $i++) {
            $documents[] = Bson::decDocument($data, $offset);
        }

        return [
            'result'   => $documents,
            'cursorId' => Util::decodeInt64($vars['cursorId1'], $vars['cursorId2']) ?: null,
            'start'    => $vars['startingFrom'],
            'count'    => $vars['numberReturned'],
        ];
    }

    public function opGetMore($fullCollectionName, $limit, $cursorId)
    {
        // do request
        $data = pack('Va*Va8', 0, "$fullCollectionName\0", $limit, Util::encodeInt64($cursorId));
        $requestId = $this->sendMessage(self::OP_GET_MORE, $data);

        // get response
        return $this->opReply($requestId);
    }

    public function opDelete($fullCollectionName, array $query, array $options = [])
    {
        $flags = 0;

        // do request
        $data = pack('Va*Va*', 0, "$fullCollectionName\0", $flags,  Bson::encode($query));

        $requestId = $this->sendMessage(self::OP_DELETE, $data);
    }

    private function decodeHeader($data)
    {
        return unpack('VmessageLength/VrequestId/VresponseTo/Vopcode', $data);
    }
}

}
