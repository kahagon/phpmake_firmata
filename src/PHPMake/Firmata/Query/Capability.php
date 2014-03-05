<?php 
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query\AbstractQuery;

class Capability extends AbstractQuery {

    public function request(Device $device) {
        $device->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_CAPABILITY, 
                Firmata::SYSEX_END));
    }

    public function receive(Device $device) {
        $capability = array();
        $this->_saveVTimeVMin($device);
        $device->setVTime(0)->setVMin(1);
        $d = $device->read(1); // Firmata::SYSEX_START
        $d = $device->read(1); // Firmata::RESPONCE_CAPABILITY
        $endOfSysExData = false;
        for (;;) {
            $pinCapability = new Device\PinCapability();

            for (;;) {
                $_d = unpack('C', $device->read(1));
                $code = $_d[1];
                if ($code == 0x7F) {
                    break;
                } else if ($code == Firmata::SYSEX_END) { 
                    $endOfSysExData = true;
                    break;
                }
                $_d = unpack('C', $device->read(1));
                $resolution = $_d[1];
                $pinCapability->setCapability($code, $resolution);
            }
            $capability[] = $pinCapability;
            if ($endOfSysExData) {
                break;
            }
        }
        $this->_restoreVTimeVMin($device);

        return $capability;
    }

}
