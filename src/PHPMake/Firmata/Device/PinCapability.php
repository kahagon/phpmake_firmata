<?php
namespace PHPMake\Firmata\Device;
use PHPMake\Firmata;

class PinCapability {
    private $_name;
    private $_code;
    private $_resolution;

    public function __construct($code, $resolution = 0) {
        switch ($code) {
            case Firmata::INPUT:
                $this->_name = 'input';
                break;
            case Firmata::OUTPUT:
                $this->_name = 'output';
                break;
            case Firmata::ANALOG:
                $this->_name = 'analog';
                break;
            case Firmata::PWM:
                $this->_name = 'pwm';
                break;
            case Firmata::SERVO:
                $this->_name = 'servo';
                break;
            case Firmata::I2C:
                $this->_name = 'i2c';
                break;
            default:
                throw new Exception(sprintf('Unknown capability(%d) specified', $code));
                break;
        }
        $this->_code = $code;
        $this->_resolution = $resolution;
    }

    public function __get($name) {
        switch ($name) {
            case 'name':
                return $this->_name;
            case 'resolution':
                return $this->_resolution;
        }
    }

    public function __set($name, $value) {
        switch ($name) {
            case 'resolution':
                $this->_resolution = $value;
                break;
        }
    }

    public function isSupported() {
        return $this->_resolution != 0;
    }
}
