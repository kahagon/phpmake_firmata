<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata;
use PHPMake\Firmata\Query;
use PHPMake\Firmata\Device;

class Device extends \PHPMake\SerialPort {
    private $_savedVTime;
    private $_savedVMin;
    protected $_firmware;
    protected $_version;
    protected $_pins;
    protected $_capability;
    protected $_bufferAnalogIn;
    protected $_bufferNotAnalogIn;

    public function __construct($deviceName, $baudRate=57600) {
        parent::__construct($deviceName);
        $this->setBaudRate($baudRate)
                ->setCanonical(false)
                ->setVTime(1)
                ->setVMin(0);
        $this->_prepare();
        $this->_initPins();
    }

    private function _initPins() {
        $this->_pins = array();
        $this->_capability = $this->query(new Query\Capability());
        $totalPins = count($this->_capability);
        for ($i = 0; $i < $totalPins; $i++) {
            $pin = new Device\Pin($i);
            $pin->setCapability($this);
            $pin->updateWithQuery($this);
            $this->_pins[] = $pin;
        }
    }
    
    public function getCapability($pin) {
        if ($pin instanceof Device\Pin) {
            $pinNumber = $pin->getNumber();
        } else {
            $pinNumber = $pin;
        }
        
        if ($pinNumber >= count($this->_capability)) {
            throw new Device\Exception(
                    sprintf('specified pin(%d) does not exist', $pinNumber));
        }
        
        return $this->_capability[$pinNumber];
    }
    
    public function getPin($pinNumber) {
        if ($pinNumber >= count($this->_pins)) {
            throw new Device\Exception(
                    sprintf('specified pin(%d) does not exist', $pinNumber));
        }
        
        return $this->_pins[$pinNumber];
    }
    
    public function digitalWrite($pinNumber, $value) {
        $value = $value ? Firmata::HIGH : Firmata::LOW;
        $portNumber = self::portNumberForPin($pinNumber);
        $command = Firmata::MESSAGE_DIGITAL | $portNumber;
        $firstByte = $this->_makeFirstByteForDigitalWrite($pinNumber, $value);
        $secondByte = $this->_makeSecondByteForDigitalWrite($pinNumber, $value);
        //printf("firstByte:0b%08b, secondByte:0b%08b\n", $firstByte, $secondByte);
        $this->write(pack('CCC', $command, $firstByte, $secondByte));
        $this->_updatePinStateInPort($portNumber);
    }
    
    public function _makeFirstByteForDigitalWrite($pinNumber, $value) {
        $currentFirstByteState = 0;
        $pinLocationInPort = self::pinLocationInPort($pinNumber);
        $portNumber = self::portNumberForPin($pinNumber);
        $firstPinNumberInPort = $portNumber * 8;
        $limit = 7;
        for ($currentPinNumber = $firstPinNumberInPort, $i = 0; $i <= $limit; $currentPinNumber++, $i++) {
            if ($pinNumber == $currentPinNumber) {
                $pinDigitalState = 0;
            } else {
                $pinDigitalState 
                        = $this->_pins[$currentPinNumber]->getState() ? 1 : 0;
            }
            
            $currentFirstByteState |= $pinDigitalState<<$i;
        }
        
        return (($value << $pinLocationInPort) | $currentFirstByteState) & 0x7F;
    }
    
    public function _makeSecondByteForDigitalWrite($pinNumber, $value) {
        $currentSecondByteState = 0;
        $portNumber = self::portNumberForPin($pinNumber);
        $firstPinNumberInPort = (($portNumber + 1) * 8) - 1;
        
        if ($pinNumber == $firstPinNumberInPort) {
            $pinDigitalState = 0;
        } else {
            $pinDigitalState 
                    = $this->_pins[$firstPinNumberInPort]->getState() ? 1 : 0;
        }
        
        $currentSecondByteState |= $pinDigitalState;
        return (($value) | $currentSecondByteState) & 0x01;
    }
    
    private function _updatePinStateInPort($portNumber) {
        $firstPinNumberInPort = $portNumber * 8;
        $limit = 8;
        for ($currentPinNumber = $firstPinNumberInPort, $i = 0; $i < $limit; $i++, $currentPinNumber++) {
            $this->_pins[$currentPinNumber]->updateWithQuery($this);
        }
    }
    
    public static function pinLocationInPort($pinNumber) {
        return $pinNumber%8;
    }
    
    public static function portNumberForPin($pinNumber) {
        return floor($pinNumber/8);
    }
    
    public function query(Query $query) {
        $query->request($this);
        return $query->receive($this);
    }

    public function getFirmware() {
        return $this->_firmware;
    }

    public function getVersion() {
        return $this->_version;
    }

    public function getc() {
        $_d = unpack('C', $this->read(1));
        return $_d[1];
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

    /**
     * Wait for data sequence to arrive.
     *
     * @param int[] $byteArray array of unsigned chars
     * @return string received binary data
     */
    public function waitData(array $byteArray) {
        $data = '';
        $this->_saveVTimeVMin();
        $this->setVTime(0)->setVMin(1);
        $length = count($byteArray);
        $index = 0;
        while (true) {
            $d = $this->read(1);
            if (!$d) continue;

            $data .= $d;
            $u = unpack('C', $data);
            $t = $u[1];
            if ($t == $byteArray[$index]) {
                if ($index == $length-1) {
                    break;
                } else {
                    ++$index;
                }
            } else {
                $index = 0; // reset
            }
        }
        $this->_restoreVTimeVMin();
        return $data;
    }


    function voidBuffer() {
        $this->_saveVTimeVMin();
        $this->setVTime(1)->setVMin(0);
        while ($r=$this->read(1024))
            ;
        $this->_restoreVTimeVMin();
    }

    function _saveVTimeVMin() {
        $this->_savedVTime = $this->getVTime();
        $this->_savedVMin = $this->getVMin();
    }

    function _restoreVTimeVMin() {
        $this->setVTime($this->_savedVTime)
                ->setVMin($this->_savedVMin);
    }

    private function _prepare() {
        $versionQuery = new Query\Version(); 
        $this->_version = $versionQuery->receive($this);
        $firmwareQuery = new Query\Firmware(); 
        $this->_firmware = $firmwareQuery->receive($this);
    }
}
