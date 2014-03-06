<?php
namespace PHPMake\Firmata;
use PHPMake\SerialPort;
use PHPMake\Firmata;
use PHPMake\Firmata\Query;

class Device extends SerialPort {
    private $_savedVTime;
    private $_savedVMin;
    protected $_firmware;
    protected $_version;

    public function __construct($deviceName, $baudRate=57600) {
        parent::__construct($deviceName);
        $this->setBaudRate($baudRate)
                ->setCanonical(false)
                ->setVTime(1)
                ->setVMin(0);
        $this->_prepare();
    }

    public function query(Query $query) {
        $query->request($this);
        return $query->receive($this);
    }

    public function getFirmware() {
        return $this->_firmware;
    }

    public function getVersion() {
        return $this->_version;
    }

    public function getc() {
        $_d = unpack('C', $this->read(1));
        return $_d[1];
    }
    
    public function receiveSysEx7bitBytesData() {
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

    /**
     * Wait for data sequence to arrive.
     *
     * @param int[] $byteArray array of unsigned chars
     * @return string received binary data
     */
    public function waitData(array $byteArray) {
        $data = '';
        $this->_saveVTimeVMin();
        $this->setVTime(0)->setVMin(1);
        $length = count($byteArray);
        $index = 0;
        while (true) {
            $d = $this->read(1);
            if (!$d) continue;

            $data .= $d;
            $u = unpack('C', $data);
            $t = $u[1];
            if ($t == $byteArray[$index]) {
                if ($index == $length-1) {
                    break;
                } else {
                    ++$index;
                }
            } else {
                $index = 0; // reset
            }
        }
        $this->_restoreVTimeVMin();
        return $data;
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
        $versionQuery = new Query\Version(); 
        $this->_version = $versionQuery->receive($this);
        $firmwareQuery = new Query\Firmware(); 
        $this->_firmware = $firmwareQuery->receive($this);
    }
}
