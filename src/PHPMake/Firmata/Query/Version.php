<?php 
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query\AbstractQuery;

class Version extends AbstractQuery {

    public function request(Device $device) {
        $device->write(pack('C', 
            Firmata::QUERY_VERSION));

    }

    public function receive(Device $device) {
        $this->_saveVTimeVMin($device);
        $device->setVTime(0)->setVMin(1);
        while ($data=$device->read(1)) {
            $t = unpack('C', $data);
            $_t = $t[1];
            if ($_t==Firmata::REPORT_VERSION) {
                break;
            }
        }

        $device->setVTime(0)->setVMin(2);
        $data = $device->read(2);
        $t = unpack('H2', substr($data, 0, 1));
        $majorVersionString = $t[1];
        $t = unpack('H2', substr($data, 1, 1));
        $minorVersionString = $t[1];
        $version = (object)array(
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString);
        $this->_restoreVTimeVMin($device);
        return $version;

    }
}
