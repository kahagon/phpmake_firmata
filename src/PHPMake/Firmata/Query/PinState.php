<?php
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query\AbstractQuery;

class PinState extends AbstractQuery {
    private $_pin;

    public function __construct(Device\Pin $pin) {
        $this->_pin = $pin;
    }
    
    public function request(Device $device) {
        $device->write(pack('CCCC',
            Firmata::SYSEX_START,
            Firmata::QUERY_PIN_STATE,
            $this->_pin->getNumber(),
            Firmata::SYSEX_END));
    }

    public function receive(Device $device) {
        $this->_saveVTimeVMin($device);

        $device->setVTime(0)->setVMin(1);
        $device->getc(); // Firmata::SYSEX_START
        $device->getc(); // Firmata::RESPONSE_PIN_STATE
        $pin = $device->getc();
        $mode = $device->getc();
        if ($mode == Firmata::SYSEX_END) {
            throw new Exception(
                    sprintf('specified pin(%d) does not exist', $pin));
        }
        $this->_pin->updateMode($mode);
        $state7bitByteArray = array();
        for (;;) { 
            $byte = $device->getc();
            if ($byte == Firmata::SYSEX_END) {
                break;
            }
            
            $state7bitByteArray[] = $byte;
        }
        $this->_restoreVTimeVMin($device);

        $pinState = 0;
        $byteArrayLength = count($state7bitByteArray);
        for ($i = 0; $i < $byteArrayLength; $i++) {
            $byte = $state7bitByteArray[$i];
            $pinState |= ($byte&0x7F)<<(8*$i);
        }
        $this->_pin->updateState($pinState);
        return $this->_pin;
    }
}
