<?php
namespace PHPMake\Firmata;

/**
 *
 * @author oasynnoum
 */
class DigitalPortReport {
    private $_portNumber;
    private $_lsb = 0;
    private $_msb = 0;
    
    public function __construct($portNumber) {
        $this->_portNumber = $portNumber;
    }
    
    public function setValue($lsb, $msb) {
        $changed = array();
        for ($i = 0; $i < 7; $i++) {
            $prev = ($this->_lsb>>$i)&0x01;
            $cur = ($lsb>>$i)&0x01;
            if ($prev != $cur) {
                $changed[Device::pinNumber($i, $this->_portNumber)] = $cur;
            }
        }
        $this->_lsb = $lsb&0xFF;
        
        $i = 7;
        $prev = ($this->_msb>>$i)&0x01;
        $cur = ($msb>>$i)&0x01;
        if ($prev != $cur) {
            $changed[Device::pinNumber($i, $this->_portNumber)] = $cur;
        }
        $this->_msb = $msb&0x80;
        
        return $changed;
    }
    

    public function getPortNumber() {
        return $this->_portNumber;
    }
}
