<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;

class JsonCommandFactory {
    private static $_instance;
    private $_commands = array();

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new JsonCommandFactory();
        }

        $this->_registerCommands();
        return self::$_instance;
    }

    private function _registerCommands() {
        $this->registerCommand('queryCapability', self::_fqn('QueryCapability'));
        $this->registerCommand('digitalWrite', self::_fqn('DigitalWrite'));
    }

    private static function _fqn($className) {
        return "\\PHPMake\\Firmata\\WebSocket\\Command\\JsonCommand\\${className}";
    }

    public function registerCommand($name, $jsonCommandFullyQualifiedName) {
        $this->_commands[$name] = $commnadInterfaceFullyQualifiedName;
    }

    public function getCommand($requiredCommandName) {
        $commandName = $this->_commands[$requiredCommandName];
        if (!$commandName) {
            throw new \Exception(sprintf("command(%s) not registered\n", $message->command);
        }

        return new $commandName();
    }
}