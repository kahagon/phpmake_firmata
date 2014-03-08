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

    public function setResolution($code, $resolution) {
        if (!array_key_exists($code, $this->_capability)) {
            throw new Exception(sprintf('Unknown capability(%d) specified', $code));
        }

        $this->_capability[$code] = $resolution;
    }

    public function getResolution($code) {
        if (!array_key_exists($code, $this->_capability)) {
            throw new Exception(sprintf('Unknown capability(%d) specified', $code));
        }

        return $this->_capability[$code];
    }
    
    public function setResolutionInput($resolution) {
        $this->setResolution(Firmata::INPUT, $resolution);
    }
    
    public function getResolutionInput() {
        return $this->getResolution(Firmata::INPUT);
    }
    
    public function setResolutionOutput($resolution) {
        $this->setResolution(Firmata::OUTPUT, $resolution);
    }

    public function getResolutionOutput() {
        return $this->getResolution(Firmata::OUTPUT);
    }

    public function setResolutionAnalog($resolution) {
        $this->setResolution(Firmata::ANALOG, $resolution);
    }

    public function getResolutionAnalog() {
        return $this->getResolution(Firmata::ANALOG);
    }
    
    public function setResolutionPWM($resolution) {
        $this->setResolution(Firmata::PWM, $resolution);
    }

    public function getResolutionPWM() {
        return $this->getResolution(Firmata::PWM);
    }
    
    public function setResolutionServo($resolution) {
        $this->setResolution(Firmata::SERVO, $resolution);
    }
    
    public function getResolutionServo() {
        return $this->getResolution(Firmata::SERVO);
    }
    
    public function setResolutionI2C($resolution) {
        $this->setResolution(Firmata::I2C, $resolution);
    }
    
    public function getResolutionI2C() {
        return $this->getResolution(Firmata::I2C);
    }

    public function isSupported($code) {
        if (!array_key_exists($code, $this->_capability)) {
            return false;
        }

        return $this->_capability[$code] != 0;
    }
    
    public function isInput() {
        return $this->isSupported(Firmata::INPUT);
    }
    
    public function isOutput() {
        return $this->isSupported(Firmata::OUTPUT);
    }
    
    public function isAnalog() {
        return $this->isSupported(Firmata::ANALOG);
    }
    
    public function isPWM() {
        return $this->isSupported(Firmata::PWM);
    }
    
    public function isServo() {
        return $this->isSupported(Firmata::SERVO);
    }
    
    public function isI2C() {
        return $this->isSupported(Firmata::I2C);
    }
}
