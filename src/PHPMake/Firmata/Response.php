<?php
namespace PHPMake\Firmata;
use PHPMake\SerialPort;

interface Response {

    /**
     * Receive data from SerialPort, and return parsed data.
     * 
     * @param PHPMake\SerialPort $dev
     * @return mixed
     */
    public function receive(SerialPort $dev);
}
