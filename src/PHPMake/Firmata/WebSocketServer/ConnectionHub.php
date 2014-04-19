<?php
namespace PHPMake\Firmata\WebSocketServer;
use PHPMake\Firmata;

abstract class ConnectionHub implements ComponentInterface {

    protected $_device;
    protected $_component;
    protected $_connections;

    public function __construct(Firmata\Device $device) {
        $this->_device = $device;
        $this->_connections = new \SplObjectStorage();
    }
    public function onOpen(\Ratchet\ConnectionInterface $connection) {
        $this->_connections->attach($connection);
    }

    public function onClose(\Ratchet\ConnectionInterface $connection) {
        $this->_connections->detach($connection);
    }

    public function getConnections() {
        return $this->_connections;
    }

    public function getDevice() {
        return $this->_device;
    }

}
