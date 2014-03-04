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
        $firmwareName = '';
        $majorVersion = null;
        $minorVersion = null;

        $this->_saveVTimeVMin($device);

        $device->setVTime(0)->setVMin(1);
        $d = $device->read(1); // Firmata::SYSEX_START
        $d = $device->read(1); // Firmata::QUERY_FIRMWARE
        $majorVersion = $device->read(1);
        $minorVersion = $device->read(1);
        $firmwareName = $device->receiveSysEx7bitBytesData();
        $this->_restoreVTimeVMin($device);

        $t = unpack('H2', $majorVersion);
        $majorVersionString = $t[1];
        $t = unpack('H2', $minorVersion);
        $minorVersionString = $t[1];
    
        $firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString
        );
        return $firmware;
    }
}
