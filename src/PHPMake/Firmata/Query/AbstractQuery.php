<?php
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata\Device;
use PHPMake\Firmata\Query;

abstract class AbstractQuery implements Query {
    private $_savedVTime;
    private $_savedVMin;

    protected function _saveVTimeVMin(Device $device) {
        $this->_savedVTime = $device->getVTime();
        $this->_savedVMin = $device->getVMin();
    }

    protected function _restoreVTimeVMin(Device $device) {
        $device->setVTime($this->_savedVTime)
                ->setVMin($this->_savedVMin);
    }
}
