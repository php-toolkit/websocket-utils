<?php

namespace MyLib\WebSocket\Util\Payload;

use MyLib\WebSocket\Util\Frame\Frame;
use MyLib\WebSocket\Util\Frame\HybiFrame;

/**
 * Gets a HyBi payload
 */
class HybiPayload extends Payload
{
    protected function getFrame(): Frame
    {
        return new HybiFrame();
    }
}
