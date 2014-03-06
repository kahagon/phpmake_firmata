<?php
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query\AbstractQuery;

class Firmware extends AbstractQuery {
    
    public function request(Device $device) {
        $device->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_FIRMWARE, 
                Firmata::SYSEX_END));
    }

    public function receive(Device $device) {
        $this->_saveVTimeVMin($device);

        $device->setVTime(0)->setVMin(1);
        $device->getc(); // Firmata::SYSEX_START
        $device->getc(); // Firmata::QUERY_FIRMWARE
        $majorVersionString = $device->getc();
        $minorVersionString = $device->getc();
        $firmwareName = $device->receiveSysEx7bitBytesData();
        $this->_restoreVTimeVMin($device);
    
        $firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString
        );
        return $firmware;
    }
}
