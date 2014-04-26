<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;
use \PHPMake\Firmata\WebSocketServer\Command\CommandInterface;

abstract class JsonCommandAdapter implements CommandInterface {

    public function send(
        \Ratchet\ConnectionInterface $from,
        $commandName,
        $signature,
        $dataPlainObjectToSend)
    {
        $from->send(json_encode((object)array(
            'command' => $commandName,
            'signature' => $signature,
            'data' => $dataPlainObjectToSend
        )));
    }

}
