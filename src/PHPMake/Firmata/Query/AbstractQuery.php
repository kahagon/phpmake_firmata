<?php
namespace PHPMake\Firmata\Query;
use PHPMake\Firmata;

abstract class AbstractQuery implements Firmata\Query {
    private $_savedVTime;
    private $_savedVMin;

    protected function _saveVTimeVMin(Firmata\Stream $stream) {
        $this->_savedVTime = $stream->getVTime();
        $this->_savedVMin = $stream->getVMin();
    }

    protected function _restoreVTimeVMin(Firmata\Stream $stream) {
        $stream->setVTime($this->_savedVTime)
                ->setVMin($this->_savedVMin);
    }
}
