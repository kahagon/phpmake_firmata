PHPMake\Firmata
===============

PHPMake\Firmata is a PHP interface to communicate with Firmata devices.  
See http://firmata.org/ to get more information about Firmata.


Dependencies
=============

PHPMake\Firmata is based on PHPMake\SerialPort extension.  
You must install PHPMake\SerialPort before testing PHPMake\Firmata.  
The installation is easily. See https://github.com/oasynnoum/phpmake_serialport


Example
=======

Example for Blinking LED 
```PHP
<?php
/* initialize the device */
$device = new PHPMake\Firmata\Device('/dev/ttyACM0');
/* for Windows */
// $device = new PHPMake\Firmata\Device('COM3');

$pin13 = 13;

for ($i = 0; $i < 3; ++$i) {
  $device->digitalWrite($pin13, PHPMake\Firmata::HIGH); // light
  sleep(1);
  $device->digitalWrite($pin13, PHPMake\Firmata::LOW); // unlight
  sleep(1);
}
```
