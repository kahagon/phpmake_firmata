<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata;

/**
 * Description of Stream
 *
 * @author oasynnoum
 */
class Stream extends \PHPMake\SerialPort {
    private $_savedVTime;
    private $_savedVMin;
    private $_state;
    
    private $_bufferDigitalInput = array();
    
    public function __construct($deviceName, $baudRate=57600) {
        parent::__construct($deviceName);
        $this->setBaudRate($baudRate)
                ->setCanonical(false)
                ->setVTime(0)
                ->setVMin(0);
        
        $this->_state = self::STATE_PROCESS_INPUT;
    }
    
    public function preprocess() {
        $this->_state = self::STATE_PROCESS_INPUT;
    }
    
    public function postprocess() {
        $this->_state = self::STATE_READ_ANALOG;
        $this->_saveVTimeVMin();
        $this->setVTime(0)->setVMin(0);
        for (;;) {
            $c = $this->getc();
            if ($c === self::EOS) {
                break;
            }
        }
        $this->_state = self::STATE_PROCESS_INPUT;
        $this->_restoreVTimeVMin();
    }

    public function getc($c = null) {
        if (is_null($c)) {
            $c = $this->_getc();
        }
        
        if (strlen($c) < 1) {
            return self::EOS;
        }
        
        if ($this->_state == self::STATE_PROCESS_INPUT) {
            return $c;
        }
        
        if ($this->_state == self::STATE_READ_ANALOG) {
            if ($c & (Firmata::MESSAGE_ANALOG&0xFF)) {
                $this->_readAnalog($c);
                $c = $this->getc();
                return $c;
            } else {
                $this->_state = self::STATE_REPORT_I2C;
                $c = $this->getc($c);
                return $c;
            }
        }
        
        if ($this->_state == self::STATE_REPORT_I2C) {
            if ($c == Firmata::SYSEX_START) {
                $this->_readI2C($c);
                return self::EOS;
            } else {
                $this->_state = self::STATE_CHECK_DIGITAL;
                $c = $this->getc($c);
                return $c;
            }
        }
        
        if ($this->_state == self::STATE_CHECK_DIGITAL) {
            if ($c & (Firmata::MESSAGE_DIGITAL&0xFF)) {
                $this->_readPort($c);
                $c = $this->getc();
                return $c;
            } else {
                $this->_state = self::STATE_PROCESS_INPUT;
                $c = $this->getc($c);
                return $c;
            }
        }
    }
    
    private function _readPort($c) {
        $this->_bufferDigitalInput[] = $c;
        $this->_bufferDigitalInput[] = $this->_getc();
        $this->_bufferDigitalInput[] = $this->_getc();
    }
    
    private function _readAnalog($c) {
        
    }
    
    private function _readI2C($c) {
        
    }
    
    private function _getc() {
        $d = $this->read(1);
        if (strlen($d) > 0) {
            $_c = unpack('C', $d);
            
            $c = $_c[1];
        } else {
            $c = self::EOS;
        }
        
        //printf('0x%02X ', $c);
        return $c;
    }
    
    public function isPreprocess() {
        $ret = false;
        switch ($this->_state) {
            case self::STATE_CHECK_DIGITAL:
                $ret = true;
                break;
            default:
                $ret = false;
                break;
        }
        
        return $ret;
    }
    
    public function isPostprocess() {
        $ret = false;
        switch ($this->_state) {
            case self::STATE_READ_ANALOG:
            case self::STATE_REPORT_I2C:
                $ret = true;
                break;
            default:
                $ret = false;
                break;
        }
        
        return $ret;
    }
    
    function _saveVTimeVMin() {
        $this->_savedVTime = $this->getVTime();
        $this->_savedVMin = $this->getVMin();
    }

    function _restoreVTimeVMin() {
        $this->setVTime($this->_savedVTime)
                ->setVMin($this->_savedVMin);
    }
    
    public function receive7bitBytesData($length) {
        if (($length%2) != 0) {
            throw new Exception(sprintf(
                    '$length(%d) is invalid. the argument must be multiple of 2.', 
                    $length));
        }
        
        $data7bitByteArray = array();
        for ($i = 0; $i < $length; $i++) {
            $d=$this->read(1);
            $_d = unpack('C', $d);
            $data7bitByteArray[] = $_d[1];
        }
        
        return self::dataWith7bitBytesArray($data7bitByteArray);
    }
    
    public function receiveSysEx7bitBytesData() {
        $data7bitByteArray = array();
        while ($d=$this->read(1)) {
            $_d = unpack('C', $d);
            if (Firmata::SYSEX_END==$_d[1]) {
                break;
            }
            $data7bitByteArray[] = $_d[1];
        }
        
        return self::dataWith7bitBytesArray($data7bitByteArray);
    }
    
    public static function dataWith7bitBytesArray(array $data7bitByteArray) {
        $data = '';
        $length = count($data7bitByteArray);
        for ($i=0; $i<$length-1; $i+=2) {
            $firstValue = $data7bitByteArray[$i] & 0x7F;
            $secondValue = ($data7bitByteArray[$i+1] & 0x7F)<<7;

            $data .= pack('C', $firstValue|$secondValue);
        }

        return $data;
    }
    
    const STATE_CHECK_DIGITAL = 'STATE_CHECK_DIGITAL';
    const STATE_PROCESS_INPUT = 'STATE_PROCESS_INPUT';
    const STATE_READ_ANALOG = 'STATE_READ_ANALOG';
    const STATE_REPORT_I2C = 'STATE_REPORT_I2C';

    const EOS = '';
}
