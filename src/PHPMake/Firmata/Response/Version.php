<?php
namespace PHPMake\Firmata\Response;
use PHPMake\SerialPort;
use PHPMake\Firmata;
use PHPMake\Firmata\Response;

class Version implements Response {

    public function receive(SerialPort $dev) {
        $dev->waitData(array(Firmata::REPORT_VERSION));

        $orgVTime = $dev->getVTime();
        $orgVMin = $dev->getVMin();
        $dev->setVTime(0)->setVMin(2);
        $data = $dev->read(2);
        $t = unpack('H2', substr($data, 0, 1));
        $majorVersionString = $t[1];
        $t = unpack('H2', substr($data, 1, 1));
        $minorVersionString = $t[1];
        $version = (object)array(
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString);

        $dev->setVTime($orgVTime)->setVMin($orgVMin);
        return $version;

    }

}
