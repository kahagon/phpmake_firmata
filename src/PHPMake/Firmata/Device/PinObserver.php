<?php
/**
 * @author oasynnoum <k.ahagon@n-3.so>
 */

namespace PHPMake\Firmata\Device;
use PHPMake\Firmata\Device;

/**
 * Interface that receive notification from device when pin state changed.
 */
interface PinObserver {

    /**
     * This method will be invoked when pin state changed.
     *
     * @param \PHPMake\Firmata\Device $device
     * @param \PHPMake\Firmata\Device\Pin $pin
     * @param int $state 
     * @return void
     */
    public function notify(Device $device, Device\Pin $pin, $state);
}
