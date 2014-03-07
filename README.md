PHPMake\Firmata
===============

PHPMake\Firmata is a PHP interface to communicate with Firmata devices.  
See http://firmata.org/ to get more information about Firmata.


Prerequisites
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

for ($i = 0; $i < 3; ++$i) {
  $device->digitalWrite(13, 1); // light
  sleep(1);
  $device->digitalWrite(13, 0); // unlight
  sleep(1);
}
```
