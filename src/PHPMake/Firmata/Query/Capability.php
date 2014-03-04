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
            $pin = array(
                Firmata::INPUT => new Device\PinCapability(Firmata::INPUT),
                Firmata::OUTPUT => new Device\PinCapability(Firmata::OUTPUT),
                Firmata::ANALOG => new Device\PinCapability(Firmata::ANALOG),
                Firmata::PWM => new Device\PinCapability(Firmata::PWM),
                Firmata::SERVO => new Device\PinCapability(Firmata::SERVO),
                Firmata::I2C => new Device\PinCapability(Firmata::I2C),
            );

            for (;;) {
                $_d = unpack('C', $device->read(1));
                $pinCapabilityCode = $_d[1];
                if ($pinCapabilityCode == 0x7F) {
                    break;
                } else if ($pinCapabilityCode == Firmata::SYSEX_END) { 
                    $endOfSysExData = true;
                    break;
                }
                $_d = unpack('C', $device->read(1));
                $resolution = $_d[1];
                $pin[$pinCapabilityCode]->resolution = $resolution;
            }
            $capability[] = $pin;
            if ($endOfSysExData) {
                break;
            }
        }
        $this->_restoreVTimeVMin($device);

        return $capability;
    }

}
