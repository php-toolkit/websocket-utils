<?php

namespace MyLib\WebSocket\Util\Protocol;

use MyLib\WebSocket\Util\Payload\HybiPayload;

/**
 * Class HybiProtocol
 * @package MyLib\WebSocket\Util\Protocol
 * @see http://tools.ietf.org/html/rfc6455#section-5.2
 */
abstract class HybiProtocol extends Protocol
{
    public function getPayload()
    {
        return new HybiPayload();
    }
}
