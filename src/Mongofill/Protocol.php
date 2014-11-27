<?php

namespace Mongofill;

class Protocol
{
    const OP_REPLY = 1;
    const OP_MSG = 1000;
    const OP_UPDATE = 2001;
    const OP_INSERT = 2002;
    const OP_QUERY = 2004;
    const OP_GET_MORE = 2005;
    const OP_DELETE = 2006;
    const OP_KILL_CURSORS = 2007;

    const QF_TAILABLE_CURSOR = 2;
    const QF_SLAVE_OK = 4;
    const QF_OPLOG_REPLAY = 8;
    const QF_NO_CURSOR_TIMEOUT = 16;
    const QF_AWAIT_DATA = 32;
    const QF_PARTIAL = 64;

    const MSG_HEADER_SIZE = 16;

    private $socket;

    private static $lastRequestId = 3;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }

    public function opInsert(
        $fullCollectionName,
        array $documents,
        array $options,
        $timeout
    )
    {
        $flags = 0;
        if (!empty($options['continueOnError'])) {
            $flags |= 1;
        }

        $data = pack(
            'Va*a*',
            $flags,
            "$fullCollectionName\0",
            bson_encode_multiple($documents)
        );

        return $this->putWriteMessage(self::OP_INSERT, $data, $options, $timeout);
    }

    public function opUpdate(
        $fullCollectionName,
        array $query,
        array $update,
        array $options,
        $timeout
    )
    {
        $flags = 0;
        if (!empty($options['upsert'])) {
            $flags |= 1;
        }

        if (!empty($options['multiple'])) {
            $flags |= 2;
        }

        $data = pack(
            'Va*Va*a*',0,
            "$fullCollectionName\0",
            $flags,
            bson_encode($query),
            bson_encode($update)
        );

        return $this->putWriteMessage(self::OP_UPDATE, $data, $options, $timeout);
    }

    public function opDelete(
        $fullCollectionName,
        array $query,
        array $options,
        $timeout
    )
    {
        $flags = 0;
        if (!empty($options['justOne'])) {
            $flags |= 1;
        }

        $data = pack(
            'Va*Va*',
            0,
            "$fullCollectionName\0",
            $flags,
            bson_encode($query)
        );

        return $this->putWriteMessage(self::OP_DELETE, $data, $options, $timeout);
    }

    public function opQuery(
        $fullCollectionName,
        array $query,
        $numberToSkip,
        $numberToReturn,
        $flags,
        $timeout,
        array $returnFieldsSelector = null
    )
    {
        $data = pack(
            'Va*VVa*',
            $flags,
            "$fullCollectionName\0",
            $numberToSkip,
            $numberToReturn,
            bson_encode($query)
        );

        if ($returnFieldsSelector) {
            $data .= bson_encode($returnFieldsSelector);
        }

        return $this->putReadMessage(self::OP_QUERY, $data, $timeout);
    }

    public function opGetMore($fullCollectionName, $limit, $cursorId, $timeout)
    {
        $data = pack('Va*Va8', 0, "$fullCollectionName\0", $limit, Util::encodeInt64($cursorId));

        return $this->putReadMessage(self::OP_GET_MORE, $data, $timeout);
    }

    public function opKillCursors(
        array $cursors,
        array $options,
        $timeout
    )
    {
        $binCursors = array_reduce(
            $cursors,
            function ($bin, $cursor) {
                return $bin .= Util::encodeInt64($cursor);
            },
            ''
        );

        $data = pack('VVa*', 0, count($cursors), $binCursors);

        return $this->putWriteMessage(self::OP_KILL_CURSORS, $data, $options, $timeout);
    }

    public function getServerHash()
    {
        return $this->socket->getServerHash();
    }

    protected function putWriteMessage($opCode, $opData, array $options, $timeout)
    {
        return $this->socket->putWriteMessage($opCode, $opData, $options, $timeout);
    }

    protected function putReadMessage($opCode, $opData, $timeout)
    {
        return $this->socket->putReadMessage($opCode, $opData, $timeout);
    }
}
