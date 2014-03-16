<?php
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Query\AbstractQuery;

class Firmware extends AbstractQuery {
    
    public function request(Firmata\Stream $stream) {
        $stream->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_FIRMWARE, 
                Firmata::SYSEX_END));
    }

    public function receive(Firmata\Stream $stream) {
        $this->_saveVTimeVMin($stream);

        $stream->setVTime(0)->setVMin(1);
        $c = $stream->getc(); // Firmata::SYSEX_START
        $c = $stream->getc(); // Firmata::QUERY_FIRMWARE
        $majorVersionString = $stream->getc();
        $minorVersionString = $stream->getc();
        $firmwareName = $stream->receiveSysEx7bitBytesData();
        $this->_restoreVTimeVMin($stream);
    
        $firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString
        );
        return $firmware;
    }
}
