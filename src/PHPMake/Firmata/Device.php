<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;

class Device extends \PHPMake\SerialPort {
    private $_state = self::STATE_SETUP;
    private $_putbackBuffer = array();
    private $_logger;
    private $_loop = false;
    private $_noop = true;
    protected $_firmware;
    protected $_version;
    protected $_pins;
    protected $_capability = null;
    protected $_digitalPortObserver;
    private $_digitalPortReportArray = array();

    public function __construct($deviceName, $baudRate=57600) {
        parent::__construct($deviceName);
        $this->_logger = Firmata::getLogger();
        $this->setBaudRate($baudRate)
                ->setCanonical(false)
                ->setVTime(0)->setVMin(1); // never modify
        $this->_setup();
        $this->_initPins();
    }
    
    public function waitData(array $byteArray) {
        $buffer = array();
        $length = count($byteArray);
        $index = 0;
        while (true) {
            $t = $this->_getc();
            $c = $byteArray[$index];
            $this->_logger->debug(sprintf('$t: %s, $c: %s'.PHP_EOL, $t, $c));
            if ($c == Firmata::ANY_BYTE || $t == $c) {
                $this->_logger->debug('match' . PHP_EOL);
                $buffer[] = $t;
                if ($index == $length-1) {
                    break;
                } else {
                    ++$index;
                }
            } else {
                $this->_logger->debug('reset' . PHP_EOL);
                $index = 0; // reset
                unset($buffer);
                $buffer = array();
            }
        }
        
        return $buffer;
    }
    
    private function _setup() {
        $buffer = $this->waitData(array(
            Firmata::REPORT_VERSION,
            Firmata::ANY_BYTE,
            Firmata::ANY_BYTE,
            Firmata::SYSEX_START,
            Firmata::QUERY_FIRMWARE,
        ));
        
        array_shift($buffer);
        $majorVersion = array_shift($buffer);
        $minorVersion = array_shift($buffer);
        $this->_version = (object)array(
            'major' => (int)$majorVersion,
            'minor' => (int)$minorVersion);
        
        array_shift($buffer); // Firmata::SYSEX_START
        array_shift($buffer); // Firmata::QUERY_FIRMWARE
        $this->_getc(); // equal to $majorVersion
        $this->_getc(); // equal to $minorVersion
        
        $firmwareName = $this->receiveSysEx7bitBytesData();
        $this->_firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => (int)$majorVersion,
            'minorVersion' => (int)$minorVersion,
        );
        
        $this->_state = self::STATE_CHECK_DIGITAL;
    }
    
    private function _preReadCapability() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->_capability = array();
        $buffer = array();
        $pinCount = 0;
        
        $c = $buffer[] = $this->_getc(); // Firmata::SYSEX_START
        if ($c != Firmata::SYSEX_START) {
            throw new Exception('unexpected char received');
        }
        $c = $buffer[] = $this->_getc(); // Firmata::RESPONSE_CAPABILITY
        if ($c != Firmata::RESPONSE_CAPABILITY) {
            throw new Exception('unexpected char received');
        }
        
        $endOfSysExData = false;
        for (;;) {
            for (;;) {
                $buffer[] = $code = $this->_getc();
                if ($code == 0x7F) {
                    ++$pinCount;
                    break;
                } else if ($code == Firmata::SYSEX_END) { 
                    $endOfSysExData = true;
                    break;
                }
                $buffer[] = $this->_getc();
            }
            
            if ($endOfSysExData) {
                break;
            }
        }
        
        $bufferLength = count($buffer);
        for ($i = $bufferLength-1; $i >= 0; $i--) {
            $c = $buffer[$i];
            $this->_logger->debug(sprintf('0x%02X ', $c));
            $this->_putback($c);
        }
        $this->_logger->debug(PHP_EOL);
        
        for ($i = 0; $i < $pinCount; $i++) {
            $this->_capability[] = new Device\PinCapability();
            $this->_pins[] = new Device\Pin($i);
        }
        $this->_logger->debug('pinCount: '.$pinCount . PHP_EOL);
    }
    
    private function _processInputSysexCapability() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        if (is_null($this->_capability)) {
            $this->_preReadCapability();
        }
        
        $c = $this->_getc(); // Firmata::SYSEX_START
        if ($c != Firmata::SYSEX_START) {
            throw new Exception('unexpected char received');
        }
        $c = $this->_getc(); // Firmata::RESPONSE_CAPABILITY
        if ($c != Firmata::RESPONSE_CAPABILITY) {
            throw new Exception('unexpected char received');
        }
        
        foreach ($this->_capability as $pinCapability) {
            for (;;) {
                $code = $this->_getc();
                if ($code == 0x7F) {
                    break;
                }
                
                $exponentOf2ForResolution = $this->_getc();
                $resolution = pow(2, $exponentOf2ForResolution);
                $pinCapability->setResolution($code, $resolution);
            }
            
        }
        
        $c = $this->_getc();
        if ($c != Firmata::SYSEX_END) {
            throw new Exception('unexpected char received');
        }
    }
    
    private function _processInputSysexPinState() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->_getc(); // Firmata::SYSEX_START
        $this->_getc(); // Firmata::RESPONSE_PIN_STATE
        $pinNumber = $this->_getc();
        $mode = $this->_getc();
        if ($mode == Firmata::SYSEX_END) {
            throw new Exception(
                    sprintf('specified pin(%d) does not exist', $pinNumber));
        }
        $pin = $this->getPin($pinNumber);
        $pin->updateMode($mode);
        $state7bitByteArray = array();
        for (;;) { 
            $byte = $this->_getc();
            if ($byte == Firmata::SYSEX_END) {
                break;
            }
            
            $state7bitByteArray[] = $byte;
        }

        $pinState = 0;
        $byteArrayLength = count($state7bitByteArray);
        for ($i = 0; $i < $byteArrayLength; $i++) {
            $byte = $state7bitByteArray[$i];
            $pinState |= ($byte&0x7F)<<(8*$i);
        }
        $pin->updateState($pinState);
    }
    
    private function _processInputSysexFirmware() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->_getc(); // Firmata::SYSEX_START
        $this->_getc(); // Firmata::QUERY_FIRMWARE
        $majorVersion = $this->_getc();
        $minorVersion = $this->_getc();
        $firmwareName = $this->receiveSysEx7bitBytesData();
        $this->_firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => (int)$majorVersion,
            'minorVersion' => (int)$minorVersion,
        );
    }

    private function _processInputSysex() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $s = $this->_getc(); // assume Firmata::SYSEX_START
        $c = $this->_getc();
        switch ($c) {
            case Firmata::RESPONSE_CAPABILITY:
                $this->_putback($c);
                $this->_putback($s);
                $this->_processInputSysexCapability();
                break;
            case Firmata::RESPONSE_PIN_STATE:
                $this->_putback($c);
                $this->_putback($s);
                $this->_processInputSysexPinState();
                break;
            case Firmata::QUERY_FIRMWARE:
                $this->_putback($c);
                $this->_putback($s);
                $this->_processInputSysexFirmware();
                break;
            default:
                throw new Exception(sprintf('unknown sysex command(0x%02X) detected', $c));
        }
    }
    
    private function _processInputVersion() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $c = $this->_getc(); // assume Firmata::REPORT_VERSION
        $majorVersion = $this->_getc();
        $minorVersion = $this->_getc();
        $this->_version = (object)array(
            'major' => (int)$majorVersion,
            'minor' => (int)$minorVersion);
    }
    
    private function _processInput() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $c = $this->_getc();
        switch ($c) {
            case Firmata::SYSEX_START:
                $this->_putback($c);
                $this->_processInputSysex();
                break;
            case Firmata::REPORT_VERSION:
                $this->_putback($c);
                $this->_processInputVersion();
                break;
            case Firmata::MESSAGE_ANALOG:
                $this->_putback($c);
                break;
            default:
                throw new Exception(sprintf(
                        'unknown command(0x%02X) detected', $c));
        }
        
        $this->_state = self::STATE_READ_ANALOG;
    }
    
    public function setDigitalPortObserver(Device\DigitalPortObserver $observer) {
        $this->_digitalPortObserver = $observer;
    }
    
    public function removeDigitalPortObserver() {
        $this->_digitalPortObserver = null;
    }
    
    private function _checkDigital() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $command = $this->_getc(); // assume Firmata::MESSAGE_DIGITAL
        $lsb = $this->_getc();
        $msb = $this->_getc();
        
        if ($this->_digitalPortObserver) {
            $this->_logger->debug('$this->_digitalPortObserver is valid' . PHP_EOL);
            $portNumber = $command&((~Firmata::MESSAGE_DIGITAL)&0xFF);
            $this->_logger->debug(sprintf("0b%08b\n", $portNumber));
            $report = $this->_getDigitalPortReport($portNumber);
            $changed = $report->setValue($lsb, $msb);
            foreach ($changed as $pinNumber => $state) {
                $this->_digitalPortObserver->notify($this, $pinNumber, $state);
            }
        }
    }
    
    
    private function _getDigitalPortReport($portNumber) {
        if (!array_key_exists($portNumber, $this->_digitalPortReportArray)) {
            $this->_digitalPortReportArray[$portNumber]
                    = new Firmata\DigitalPortReport($portNumber);
        }
        
        return $this->_digitalPortReportArray[$portNumber];
    }
    
    private function _processCheckDigital() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $c = $this->_getc();
        if (($c&0xF0) == Firmata::MESSAGE_DIGITAL) {
            $this->_logger->debug('message is digital'. PHP_EOL);
            $this->_putback($c);
            $this->_checkDigital();
        } else {
            $this->_logger->debug('message is not digital'. PHP_EOL);
            $this->_putback($c);
            $this->_state = self::STATE_PROCESS_INPUT;
        }
    }
    
    private function _eval() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->_noop = false;
        $recursion = true;
        switch ($this->_state) {
            case self::STATE_CHECK_DIGITAL:
                $this->_processCheckDigital();
                break;
            case self::STATE_PROCESS_INPUT:
                $this->_processInput();
                break;
            case self::STATE_READ_ANALOG:
                $this->_state = self::STATE_REPORT_I2C;
                //$recursion = false;
                break;
            case self::STATE_REPORT_I2C:
                $this->_state = self::STATE_CHECK_DIGITAL;
                $recursion = false;
                break;
            default:
                throw new Exception('stream got unkown state');
        }
        
        if ($recursion) {
            $this->_eval();
        }
    }
    
    private function _getc() {
        if (count($this->_putbackBuffer) > 0) {
            return array_shift($this->_putbackBuffer);
        }
        
        $d = $this->read(1);
        if (strlen($d) > 0) {
            $_c = unpack('C', $d);
            
            $c = $_c[1];
            //printf('0x%02X ', $c);
        } else {
            $c = null;
        }
        
        return $c;
    }
    
    private function _putback($c) {
        array_unshift($this->_putbackBuffer, $c);
    }
    
    public function stopLoop() {
        $this->_loop = false;
    }
    
    public function startLoop(Device\LoopDelegate $delegate) {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->_loop = true;
        $interval = $delegate->getInterval();
        while ($this->_loop) {
            $delegate->loop($this);
            if ($this->_noop) {
                $this->_noop();
            }
            $this->_noop = true;
            usleep($interval);
        }
    }
    
    private function _noop() {
        $this->queryVersion();
    }
    
    private function _initCapability() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_CAPABILITY, 
                Firmata::SYSEX_END));
        $this->_eval();
    }
    
    private function _initPins() {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $this->_pins = array();
        $this->_initCapability();
        $this->_logger->debug('state:' . $this->_state . PHP_EOL);
        $totalPins = count($this->_capability);
        for ($i = 0; $i < $totalPins; $i++) {
            $pin = $this->_pins[$i];
            $pin->setCapability($this->_capability[$i]);
            $this->updatePin($pin);
        }
    }
    
    public function updatePin($pin) {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $pinNumber = $this->_pinNumber($pin);
        $this->write(pack('CCCC',
            Firmata::SYSEX_START,
            Firmata::QUERY_PIN_STATE,
            $pinNumber,
            Firmata::SYSEX_END));
        $this->_eval();
    }
    
    private function _pinNumber($pin) {
        if ($pin instanceof Device\Pin) {
            $pinNumber = $pin->getNumber();
        } else {
            $pinNumber = $pin;
        }
        
        if ($pinNumber >= count($this->_capability)) {
            throw new Device\Exception(
                    sprintf('specified pin(%d) does not exist', $pinNumber));
        }
        
        return $pinNumber;
    }
    
    public function getCapability($pin) {
        $pinNumber = $this->_pinNumber($pin);
        return $this->_capability[$pinNumber];
    }
    
    public function getPin($pin) {
        $pinNumber = $this->_pinNumber($pin);
        return $this->_pins[$pinNumber];
    }
    
    public function setPinMode($pin, $mode) {
        $pin = $this->getPin($pin);
        $this->write(pack('CCC',
            Firmata::SET_PIN_MODE,
            $pin->getNumber(),
            $mode
        ));
        $this->updatePin($pin);
    }
    
    public function reportDigitalPort($portNumber, $report=true) {
        $command = Firmata::REPORT_DIGITAL | $portNumber;
        $this->write(pack('CC', $command, $report?1:0));
    }
    
    public function digitalWrite($pin, $value) {
        $this->_logger->debug(__METHOD__.PHP_EOL);
        $pinNumber = $this->_pinNumber($pin);
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
        for (
                $currentPinNumber = $firstPinNumberInPort, $i = 0; 
                $i < $limit; 
                $i++, $currentPinNumber++) 
        {
            $this->updatePin($currentPinNumber);
        }
    }
    
    public static function pinLocationInPort($pinNumber) {
        return $pinNumber%8;
    }
    
    public static function portNumberForPin($pinNumber) {
        return floor($pinNumber/8);
    }
    
    public static function pinNumber($pinLocationInPort, $portNumber) {
        return $portNumber*8 + $pinLocationInPort;
    }
    
    public function queryFirmware() {
        $this->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_FIRMWARE, 
                Firmata::SYSEX_END));
        $this->_eval();
        return $this->_firmware;
    }
    
    public function getFirmware() {
        return $this->_firmware;
    }
    
    public function queryVersion() {
        $this->write(pack('C', 
            Firmata::REPORT_VERSION));
        $this->_eval();
        return $this->_version;
    }

    public function getVersion() {
        return $this->_version;
    }
    
    public function receive7bitBytesData($length) {
        if (($length%2) != 0) {
            throw new Exception(sprintf(
                    '$length(%d) is invalid. the argument must be multiple of 2.', 
                    $length));
        }
        
        $data7bitByteArray = array();
        for ($i = 0; $i < $length; $i++) {
            $c = $this->_getc();
            $data7bitByteArray[] = $c;
        }
        
        return self::dataWith7bitBytesArray($data7bitByteArray);
    }
    
    public function receiveSysEx7bitBytesData() {
        $data7bitByteArray = array();
        while (($c=$this->_getc()) != Firmata::SYSEX_END) {
            $data7bitByteArray[] = $c;
        }
        
        return self::dataWith7bitBytesArray($data7bitByteArray);
    }
    
    public static function dataWith7bitBytesArray(array $data7bitByteArray) {
        $data = '';
        $length = count($data7bitByteArray);
        if (($length%2) != 0) {
            throw new Exception(sprintf(
                'array length(%d) is invalid. length must be multiple of 2.', 
                $length));
        }
        
        for ($i=0; $i<$length-1; $i+=2) {
            $firstValue = $data7bitByteArray[$i] & 0x7F;
            $secondValue = ($data7bitByteArray[$i+1] & 0x7F)<<7;

            $data .= pack('C', $firstValue|$secondValue);
        }

        return $data;
    }
    
    const STATE_SETUP = 'STATE_SETUP';
    const STATE_CHECK_DIGITAL = 'STATE_CHECK_DIGITAL';
    const STATE_PROCESS_INPUT = 'STATE_PROCESS_INPUT';
    const STATE_READ_ANALOG = 'STATE_READ_ANALOG';
    const STATE_REPORT_I2C = 'STATE_REPORT_I2C';
}
