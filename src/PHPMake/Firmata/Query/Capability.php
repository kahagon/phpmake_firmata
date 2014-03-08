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
        $device->getc(); // Firmata::SYSEX_START
        $device->getc(); // Firmata::RESPONSE_CAPABILITY
        $endOfSysExData = false;
        for (;;) {
            $pinCapability = new Device\PinCapability();

            for (;;) {
                $code = $device->getc();
                if ($code == 0x7F) {
                    break;
                } else if ($code == Firmata::SYSEX_END) { 
                    $endOfSysExData = true;
                    break;
                }
                $exponentOf2ForResolution = $device->getc();
                $resolution = pow(2, $exponentOf2ForResolution);
                $pinCapability->setResolution($code, $resolution);
            }
            
            if ($endOfSysExData) {
                break;
            } else {
                $capability[] = $pinCapability;    
            }
        }
        $this->_restoreVTimeVMin($device);

        return $capability;
    }

}
