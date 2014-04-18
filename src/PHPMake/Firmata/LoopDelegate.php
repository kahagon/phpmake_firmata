<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata\Device;

/**
 *
 * @author oasynnoum
 */
interface LoopDelegate {
    public function tick(Device $device);
    public function getInterval();
}
