<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;
use \PHPMake\Firmata;

class QueryPinState extends JsonCommandAdapter {

    public function execute(
        $commandName,
        $signature,
        array $arguments,
        Firmata\Device $device,
        \Ratchet\ConnectionInterface $from,
        \Iterator $connections)
    {
        $targetPin = $arguments[0];
        $device->updatePin($targetPin);
        $data = (object)array(
            'pin' => $targetPin,
            'state' => $device->getPin($targetPin)->getState()
        );
        $this->send($from, $commandName, $signature, $data);
    }
}
