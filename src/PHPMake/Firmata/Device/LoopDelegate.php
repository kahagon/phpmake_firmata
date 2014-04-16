<?php
namespace PHPMake\Firmata\Device;
use PHPMake\Firmata\Device;

/**
 *
 * @author oasynnoum
 */
interface LoopDelegate {
    public function tick(Device $device);
    public function getInterval();
}
