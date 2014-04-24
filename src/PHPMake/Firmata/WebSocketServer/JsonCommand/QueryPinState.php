<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;
use \PHPMake\Firmata;
use \PHPMake\Firmata\WebSocketServer\Command\CommandInterface;

class QueryPinState implements CommandInterface {

    public function execute(
        $commandName,
        array $arguments,
        Firmata\Device $device,
        \Ratchet\ConnectionInterface $from,
        \Iterator $connections)
    {
        $targetPin = $arguments[0];
        $device->updatePin($targetPin);
        $from->send(json_encode((object)array(
            'command' => $commandName,
            'data' => (object)array(
                'pin' => $targetPin,
                'state' => $device->getPin($targetPin)->getState()
            )
        )));
    }
}
