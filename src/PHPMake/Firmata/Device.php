<?php
namespace PHPMake\Firmata;
require_once dirname(__FILE__) . '/../Firmata.php';
use PHPMake\SerialPort as SerialPort;
use PHPMake\Firmata as Firmata;

class Device extends SerialPort {
    private $_deviceName;
    private $_savedVTime;
    private $_savedVMin;

    public function __construct($deviceName, $baudRate=57600) {
        parent::__construct($deviceName);
        $this->setBaudRate($baudRate)
                ->setCanonical(false)
                ->setVTime(1)
                ->setVMin(0);
        $ret = $this->flush();
        $this->_prepare();
    }
    
    public function queryFirmwareVersion() {
        $this->voidBuffer();
        $this->_requestFirmwareVersion();
        return $this->_receiveFirmwareVersion();
    }
    private function _requestFirmwareVersion() {
        $this->write(pack('CCC', 
                Firmata::SYSEX_START, 
                Firmata::QUERY_FIRMWARE, 
                Firmata::SYSEX_END));
    }
    private function _receiveFirmwareVersion() {
        $firmwareName = '';
        $majorVersion = null;
        $minorVersion = null;

        $this->_saveVTimeVMin();

        $this->setVTime(0)->setVMin(1);
        $d = $this->read(1); // Firmata::SYSEX_START
        $d = $this->read(1); // Firmata::QUERY_FIRMWARE
        $majorVersion = $this->read(1);
        $minorVersion = $this->read(1);
        
        while ($d=$this->read(1)) {
            $_d = unpack('C', $d);
            if (Firmata::SYSEX_END==$_d[1]) {
                break;
            }
            $firmwareName .= $d;
        }
        $this->_restoreVTimeVMin();

        $t = unpack('H2', $majorVersion);
        $majorVersionString = $t[1];
        $t = unpack('H2', $minorVersion);
        $minorVersionString = $t[1];
    
        $firmware = (object)array(
            'name' => $firmwareName,
            'majorVersion' => $majorVersionString,
            'minorVersion' => $minorVersionString
        );
        return $firmware;
    }

    private function _receiveVersion() { 
        $this->_saveVTimeVMin();
        $this->setVTime(0)->setVMin(3);
        $data = $this->read(3);
        $t = unpack('H2', substr($data, 1, 1));
        $majorVersionString = $t[1];
        $t = unpack('H2', substr($data, 2, 1));
        $minorVersionString = $t[1];
        $version = (object)array(
            'majorVersion' => $majorVersionString,
            'minorVersion' => $minorVersionString);
        $this->_restoreVTimeVMin();
        return $version;
    }

    function voidBuffer() {
        $this->_saveVTimeVMin();
        $this->setVTime(1)->setVMin(0);
        while ($r=$this->read(1024))
            ;
        $this->_restoreVTimeVMin();
    }

    function _saveVTimeVMin() {
        $this->_savedVTime = $this->getVTime();
        $this->_savedVMin = $this->getVMin();
    }

    function _restoreVTimeVMin() {
        $this->setVTime($this->_savedVTime)
                ->setVMin($this->_savedVMin);
    }

    private function _prepare() {
        $this->_prepareVersion();
        $this->_prepareFirmwareVersion();
    }
    private function _prepareVersion() {
        $version = $this->_receiveVersion();
    }
    private function _prepareFirmwareVersion() {
        $firmware = $this->_receiveFirmwareVersion();
    }
}
