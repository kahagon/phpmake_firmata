<?php
namespace PHPMake\Firmata\Device;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query;

/**
 * Description of Pin
 *
 * @author oasynnoum
 */
class Pin {
    private $_number;
    private $_state;
    private $_inputState;
    private $_mode;
    private $_capability;

    public function __construct($number) {
        $this->_number = $number;
        $this->_inputState = Firmata::LOW;
    }

    public function getNumber() {
        return $this->_number;
    }

    public function getState() {
        return $this->_state;
    }

    public function getInputState() {
        return $this->_inputState;
    }

    public function getMode() {
        return $this->_mode;
    }

    public function setMode(Device $device, $mode) {
        $device->setPinMode($this, $mode);
    }

    public function setCapability(Device\PinCapability $pinCapability) {
        $this->_capability = $pinCapability;
    }

    public function getCapability() {
        return $this->_capability;
    }

    public function updateState($state) {
        $this->_state = $state;
    }

    public function updateInputState($state) {
        $this->_inputState = $state;
    }

    public function updateMode($mode) {
        $this->_mode = $mode;
    }
}
