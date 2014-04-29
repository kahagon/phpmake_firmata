<?php
namespace PHPMake\Firmata\WebSocketServer;
use \PHPMake\Firmata;
class JsonCommandController
    extends Firmata\WebSocketServer\ConnectionHub
    implements Firmata\Device\DigitalPinObserver
{
    protected $_tickInterval;

    public function __construct(Firmata\Device $device, $tickInterval = 30000) {
        parent::__construct($device);
        $device->addDigitalPinObserver($this);
        $this->_tickInterval = $tickInterval;
    }

    public function notify(Firmata\Device $dev, Firmata\Device\Pin $pin, $state) {
        foreach ($this->getConnections() as $connection) {
            $connection->send(json_encode((object)array(
                'command' => 'digitalRead',
                'signature' => null,
                'data' => (object)array(
                    'pin' => $pin->getNumber(),
                    'state' => $state
                )
            )));
        }
    }

    public function tick(Firmata\Device $dev) {

    }

    public function getInterval() {
        return $this->_tickInterval;
    }

    public function onMessage(\Ratchet\ConnectionInterface $connection, $message) {
        $factory = JsonCommand\JsonCommandFactory::getInstance();
        try {
            $poCommand = json_decode($message);
            $command = $factory->getCommand($poCommand->command);
            $command->execute(
                $poCommand->command,
                $poCommand->signature,
                $poCommand->arguments,
                $this->getDevice(),
                $connection,
                $this->getConnections());
        } catch (\Exception $e) {
            print 'exception occurred. ' . $e->getMessage() . PHP_EOL;
        }
    }

    public function onError(\Ratchet\ConnectionInterface $connection, \Exception $e) {
        print 'WebSocket connection error: ' . $e->getMessage() . PHP_EOL;
        $connection->close();
    }
}
