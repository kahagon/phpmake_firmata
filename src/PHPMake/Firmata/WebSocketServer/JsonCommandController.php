<?php
namespace PHPMake\Firmata\WebSocketServer;
class JsonCommandController extends Firmata\WebSocketServer\ConnectionHub {

    protected $_tickInterval;

    public function __construct(Firmata\Device $device, $tickInterval = 30000) {
        parent::__construct($device);
        $this->_tickInterval = $tickInterval;
    }

    public function tick(Firmata\Device $dev) {

    }

    public function getInterval() {
        return $this->_tickInterval;
    }

    public function onMessage(\Ratchet\ConnectionInterface $connection, $message) {
        $factory = Command\JsonCommandFactory::getInstance();
        try {
            $poCommand = json_decode($message);
            $command = $factory->getCommand($poCommand->command);
            $command->execute($poCommand->arguments, $connection, $this->getConnections());
        } catch (\Exception $e) {
            print 'exception occurred. ' . $e->getMessage() . PHP_EOL;
        }
    }

    public function onError(\Ratchet\ConnectionInterface $connection, \Exception $e) {
        print 'WebSocket connection error: ' . $e->getMessage() . PHP_EOL;
        $connection->close();
    }
}
