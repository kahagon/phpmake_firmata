<?php
namespace PHPMake\Firmata\WebSocketServer;

interface ComponentInterface extends 
            \PHPMake\Firmata\DeviceContainer,
            \PHPMake\Firmata\LoopDelegate, 
            \Ratchet\MessageComponentInterface
{
}
