<?php
/**
 * @author oasynnoum <k.ahagon@n-3.so>
 */

namespace PHPMake\Firmata;
use PHPMake\Firmata\Device;

/**
 * Interface to be delegated a frame of device loop.
 */
interface LoopDelegate {
    /**
     * A frame of device loop.
     * This method is invoked when the interval which defined with getInterval() elapsed.
     * 
     * @param \PHPMake\Firmata\Device $device
     * @return void
     */
    public function tick(Device $device);

    /**
     * Defines the interval for device loop.
     *
     * @return int device loop interval in microseconds
     */
    public function getInterval();
}
