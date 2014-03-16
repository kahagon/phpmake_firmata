<?php
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query\AbstractQuery;

class Firmware extends AbstractQuery {
    
    public function request(Firmata\Stream $stream) {
        $stream->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_FIRMWARE, 
                Firmata::SYSEX_END));
    }

    public function receive(Firmata\Stream $stream) {
        print __METHOD__ . PHP_EOL;
        $this->_saveVTimeVMin($stream);

        $stream->setVTime(0)->setVMin(1);
        $c = $stream->getc(); // Firmata::SYSEX_START
        printf("Firmata::SYSEX_START 0x%02X\n", $c);
        $c = $stream->getc(); // Firmata::QUERY_FIRMWARE
        printf("Firmata::QUERY_FIRMWARE 0x%02X\n", $c);
        $majorVersionString = $stream->getc();
        printf("majorVersionString %s\n", $majorVersionString);
        $minorVersionString = $stream->getc();
        printf("minorVersionString %s\n", $minorVersionString);
        $firmwareName = $stream->receiveSysEx7bitBytesData();
        printf("firmwareName:%s\n", $firmwareName);
        $this->_restoreVTimeVMin($stream);
    
        $firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString
        );
        return $firmware;
    }
}
