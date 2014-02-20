<?php

namespace Mongofill;


class Protocol
{
    const OP_UPDATE = 2001;
    const OP_INSERT = 2002;

    private $socket;

    public function __construct($socket)
    {
        $this->socket = $socket;
    }

    private function sendMessage($opCode, $opData)
    {
        $reqId = 1;
        $rspTo = 0xffffffff;
        $bytes = strlen($opData)+16;
        $bytesSent = 0;
        $payload = pack('V4', $bytes, $reqId, $rspTo, $opCode) . $opData;
        do {
            $result = fwrite($this->socket, $payload);
            if (false === $result) {
                // TODO handle write errors
                throw new \RuntimeException('unhandled socket write error');
            }
            $bytesSent += $result;
            $payload = substr($payload, $bytesSent);
        } while ($bytesSent < $bytes);
    }

    private function opUpdate($fullCollectionName, array $query, array $update, $upsert, $multi)
    {
        $flags = 0;
        if ($upsert) $flags |= 1;
        if ($multi) $flags |= 2;
        $data = pack('Ca*Va*a*', 0, "$fullCollectionName\0", $flags, Bson::encode($query), Bson::encode($update));
        $this->sendMessage($data, self::OP_UPDATE);
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
}