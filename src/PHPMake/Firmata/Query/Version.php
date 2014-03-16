<?php 
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;
use PHPMake\Firmata\Query\AbstractQuery;

class Version extends AbstractQuery {

    public function request(Firmata\Stream $stream) {
        $stream->write(pack('C', 
            Firmata::REPORT_VERSION));

    }

    public function receive(Firmata\Stream $stream) {
        $this->_saveVTimeVMin($stream);
        $stream->setVTime(0)->setVMin(1);
        while ($data=$stream->read(1)) {
            $t = unpack('C', $data);
            $_t = $t[1];
            if ($_t==Firmata::REPORT_VERSION) {
                break;
            }
        }

        $stream->setVTime(0)->setVMin(2);
        $majorVersionString = $stream->getc();
        $minorVersionString = $stream->getc();
        $version = (object)array(
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString);
        $this->_restoreVTimeVMin($stream);
        return $version;

    }
}
