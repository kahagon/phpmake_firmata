<?php
namespace PHPMake\Firmata\WebSocketServer\JsonCommand;
use \PHPMake\Firmata;

class QueryCapability extends CommandInterface {

    public function execute(
        $commandName,
        $signature,
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
            $plainObject->currentState = $pin->getState();

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

        $this->send($from, $commandName, $signature, $capabilities);
    }
}
