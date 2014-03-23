<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata;

/**
 *
 * @author oasynnoum
 */
interface DigitalPortObserver {
    public function notify(Firmata\Device $device, $pinNumber, $state);
}
