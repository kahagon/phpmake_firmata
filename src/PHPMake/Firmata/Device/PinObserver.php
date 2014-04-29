<?php
namespace PHPMake\Firmata\Device;
use PHPMake\Firmata\Device;

/**
 *
 * @author oasynnoum
 */
interface PinObserver {
    public function notify(Device $device, Device\Pin $pinNumber, $state);
}
