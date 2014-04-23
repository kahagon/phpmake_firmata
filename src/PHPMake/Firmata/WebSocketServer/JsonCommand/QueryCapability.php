<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;
use \PHPMake\Firmata;
use \PHPMake\Firmata\WebSocketServer\Command\CommandInterface;

class QueryCapability implements CommandInterface {

    public function execute(
        $commandName,
        array $arguments,
        Firmata\Device $device,
        \Ratchet\ConnectionInterface $from,
        \Iterator $connections)
    {
        $capabilities = array();
        $pinCapabilities = $device->getCapabilities();
        $length = count($pinCapabilities);
        for ($i = 0; $i < $length; $i++) {
            $pinCapability = $pinCapabilities[$i];
            $plainObject = new \StdClass;
            $plainObject->input = $pinCapability->getResolutionInput();
            $plainObject->output = $pinCapability->getResolutionOutput();
            $plainObject->analog = $pinCapability->getResolutionAnalog();
            $plainObject->pwm = $pinCapability->getResolutionPWM();
            $plainObject->servo = $pinCapability->getResolutionServo();
            $plainObject->i2c = $pinCapability->getResolutionI2C();

            $pin = $device->getPin($i);
            switch ($device->getPin($i)->getMode()) {
                case Firmata::INPUT:
                    $plainObject->currentMode = 'input';
                    break;
                case Firmata::OUTPUT:
                    $plainObject->currentMode = 'output';
                    break;
                case Firmata::ANALOG:
                    $plainObject->currentMode = 'analog';
                    break;
                case Firmata::PWM:
                    $plainObject->currentMode = 'pwm';
                    break;
                case Firmata::SERVO:
                    $plainObject->currentMode = 'servo';
                    break;
                case Firmata::I2C:
                    $plainObject->currentMode = 'i2c';
                    break;
                default:
                    $plainObject->currentMode = null;
                    break;
            }
            $capabilities[] = $plainObject;
        }
        $from->send(json_encode((object)array(
            'command' => $commandName,
            'data' => $capabilities
        )));
    }
}
