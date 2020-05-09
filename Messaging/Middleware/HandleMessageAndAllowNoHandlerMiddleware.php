<?php

namespace Morebec\OrkestraBundle\Messaging\Middleware;

use Morebec\Orkestra\Messaging\MessageHandlerMap;
use Morebec\Orkestra\Messaging\MessageHandlerProvider;

class HandleMessageAndAllowNoHandlerMiddleware extends HandleMessageMiddleware
{
    public function __construct(
        MessageHandlerProvider $handlerProvider,
        MessageHandlerMap $handlerMap
    ) {
        parent::__construct($handlerProvider, $handlerMap, true);
    }
}
