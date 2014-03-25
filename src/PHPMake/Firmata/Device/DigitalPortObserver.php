<?php
namespace PHPMake\Firmata\Device;
use PHPMake\Firmata\Device;

/**
 *
 * @author oasynnoum
 */
interface DigitalPortObserver {
    public function notify(Device $device, $pinNumber, $state);
}
