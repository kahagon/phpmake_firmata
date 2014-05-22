<?php
require dirname(__FILE__) . '/vendor/autoload.php';

use \PHPMake\Firmata;

$devName = '/dev/tty.usbmodemfa131';

class DigitalPinObserver implements Firmata\Device\PinObserver {
    public $count = 0;
    public $limit = 3;

    public function notify(Firmata\Device $dev, Firmata\Device\Pin $pin, $state) {
        if ($state) {
            $this->count += 1;
            printf("%d回押されました。\n", $this->count);
            if ($this->count == $this->limit) {
                $dev->stop();
            }
        }
    }
}

class Loop implements Firmata\LoopDelegate {

    public function tick(Firmata\Device $device) {
        //print __METHOD__.PHP_EOL;
    }

    public function getInterval() {
        return 50000; // 50 milliseconds
    }
}

$dev = new Firmata\Device($devName);
$dev->reportDigitalPin(13);
$dev->addDigitalPinObserver(new DigitalPinObserver());
$dev->run(new Loop());
print "(^_^)/~\n";
