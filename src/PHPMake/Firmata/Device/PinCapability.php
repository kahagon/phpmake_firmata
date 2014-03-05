<?php
namespace PHPMake\Firmata\Device;
use PHPMake\Firmata;

class PinCapability {
    private $_capability = array(
        Firmata::INPUT => 0,
        Firmata::OUTPUT => 0,
        Firmata::ANALOG => 0,
        Firmata::PWM => 0,
        Firmata::SERVO => 0,
        Firmata::I2C => 0,
    );

    public function setCapability($code, $resolution) {
        if (!array_key_exists($code, $this->_capability)) {
            throw new Exception(sprintf('Unknown capability(%d) specified', $code));
        }

        $this->_capability[$code] = $resolution;
    }

    public function getCapability($code) {
        if (!array_key_exists($code, $this->_capability)) {
            throw new Exception(sprintf('Unknown capability(%d) specified', $code));
        }

        return $this->_capability[$code];
    }

    public function isSupported($code) {
        if (!array_key_exists($code, $this->_capability)) {
            return false;
        }

        return $this->_capability[$code] != 0;
    }
}
