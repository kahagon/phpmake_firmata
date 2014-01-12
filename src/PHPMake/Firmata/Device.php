<?php
namespace PHPMake\Firmata;
require_once dirname(__FILE__) . '/../Firmata.php';
use PHPMake\SerialPort as SerialPort;
use PHPMake\Firmata as Firmata;

class Device extends SerialPort {
    private $_savedVTime;
    private $_savedVMin;

    public function __construct($deviceName, $baudRate=57600) {
        parent::__construct($deviceName);
        $this->setBaudRate($baudRate)
                ->setCanonical(false)
                ->setVTime(1)
                ->setVMin(0);
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
        $firmwareName = $this->_receiveSysEx7bitBytesData();
        $this->_restoreVTimeVMin();

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

    private function _receiveSysEx7bitBytesData() {
        $data7bitByteArray = array();
        $data = '';
        while ($d=$this->read(1)) {
            $_d = unpack('C', $d);
            if (Firmata::SYSEX_END==$_d[1]) {
                break;
            }
            $data7bitByteArray[] = $_d[1];
        }

        $length = count($data7bitByteArray);
        for ($i=0; $i<$length-1; $i+=2) {
            $firstValue = $data7bitByteArray[$i] & 0x7F;
            $secondValue = ($data7bitByteArray[$i+1] & 0x7F)<<7;

            $data .= pack('C', $firstValue|$secondValue);
        }

        return $data;
    }

    private function _receiveVersion() { 
        $this->_saveVTimeVMin();
        $this->setVTime(0)->setVMin(1);
        while ($data=$this->read(1)) {
            $t = unpack('C', $data);
            $_t = $t[1];
            if ($_t==Firmata::REPORT_VERSION) {
                break;
            }
        }

        $this->setVTime(0)->setVMin(2);
        $data = $this->read(2);
        $t = unpack('H2', substr($data, 0, 1));
        $majorVersionString = $t[1];
        $t = unpack('H2', substr($data, 1, 1));
        $minorVersionString = $t[1];
        $version = (object)array(
            'majorVersion' => (int)$majorVersionString,
            'minorVersion' => (int)$minorVersionString);
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
        $this->_receiveVersion();
        $this->_receiveFirmwareVersion();
    }
}
