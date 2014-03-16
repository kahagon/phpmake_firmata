<?php 
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query\AbstractQuery;

class Capability extends AbstractQuery {

    public function request(Firmata\Stream $stream) {
        $stream->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_CAPABILITY, 
                Firmata::SYSEX_END));
    }

    public function receive(Firmata\Stream $stream) {
        $capability = array();
        $this->_saveVTimeVMin($stream);
        $stream->setVTime(0)->setVMin(1);
        $stream->getc(); // Firmata::SYSEX_START
        $stream->getc(); // Firmata::RESPONSE_CAPABILITY
        $endOfSysExData = false;
        for (;;) {
            $pinCapability = new Device\PinCapability();

            for (;;) {
                $code = $stream->getc();
                if ($code == 0x7F) {
                    break;
                } else if ($code == Firmata::SYSEX_END) { 
                    $endOfSysExData = true;
                    break;
                }
                $exponentOf2ForResolution = $stream->getc();
                $resolution = pow(2, $exponentOf2ForResolution);
                $pinCapability->setResolution($code, $resolution);
            }
            
            if ($endOfSysExData) {
                break;
            } else {
                $capability[] = $pinCapability;    
            }
        }
        $this->_restoreVTimeVMin($stream);

        return $capability;
    }

}
