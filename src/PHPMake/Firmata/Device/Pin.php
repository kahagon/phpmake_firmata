<?php
namespace PHPMake\Firmata\Device;
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
    private $_mode;
    
    public function __construct($number) {
        $this->_number = $number;
    }
    
    public function getNumber() {
        return $this->_number;
    }
    
    public function getState() {
        return $this->_state;
    }
    
    public function getMode() {
        return $this->_mode;
    }
    
    public function updateState($state) {
        $this->_state = $state;
    }
    
    public function updateMode($mode) {
        $this->_mode = $mode;
    }
    
    public function updateWithQuery(Device $device) {
        $pinStateQuery = new Query\PinState($this);
        $device->query($pinStateQuery);
    }
}
