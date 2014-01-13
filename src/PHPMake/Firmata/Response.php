<?php
namespace PHPMake\Firmata;

interface Response {

    /**
     * Receive data from SerialPort, and return parsed data.
     * 
     * @param PHPMake\SerialPort $dev
     * @return mixed
     */
    public function receive(PHPMake\SerialPort $dev);

}
