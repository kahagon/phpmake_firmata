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
    
    public function request(Firmata\Stream $stream) {
        $stream->write(pack('CCCC',
            Firmata::SYSEX_START,
            Firmata::QUERY_PIN_STATE,
            $this->_pin->getNumber(),
            Firmata::SYSEX_END));
    }

    public function receive(Firmata\Stream $stream) {
        $this->_saveVTimeVMin($stream);

        $stream->setVTime(0)->setVMin(1);
        $stream->getc(); // Firmata::SYSEX_START
        $stream->getc(); // Firmata::RESPONSE_PIN_STATE
        $pin = $stream->getc();
        $mode = $stream->getc();
        if ($mode == Firmata::SYSEX_END) {
            throw new Exception(
                    sprintf('specified pin(%d) does not exist', $pin));
        }
        $this->_pin->updateMode($mode);
        $state7bitByteArray = array();
        for (;;) { 
            $byte = $stream->getc();
            if ($byte == Firmata::SYSEX_END) {
                break;
            }
            
            $state7bitByteArray[] = $byte;
        }
        $this->_restoreVTimeVMin($stream);

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
