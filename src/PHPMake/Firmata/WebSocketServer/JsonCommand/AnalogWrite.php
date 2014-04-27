<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;
use \PHPMake\Firmata;

class AnalogWrite extends JsonCommandAdapter {

    public function execute(
        $commandName,
        $signature,
        array $arguments,
        Firmata\Device $device,
        \Ratchet\ConnectionInterface $from,
        \Iterator $connections)
    {
        $pin = $arguments[0];
        $level = $arguments[1];
        $device->analogWrite($pin, $level);
        $this->send($from, $commandName, $signature, null);
    }
}
