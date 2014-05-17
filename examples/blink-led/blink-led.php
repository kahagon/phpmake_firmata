<?php
require dirname(__FILE__) . '/vendor/autoload.php';
/* initialize the device */
$device = new PHPMake\Firmata\Device('/dev/ttyACM0');
/* for Windows */
// $device = new PHPMake\Firmata\Device('COM3');

$pin = 13;

for ($i = 0; $i < 3; ++$i) {
  $device->digitalWrite($pin, PHPMake\Firmata::HIGH); // light
  sleep(1);
  $device->digitalWrite($pin, PHPMake\Firmata::LOW); // unlight
  sleep(1);
}
