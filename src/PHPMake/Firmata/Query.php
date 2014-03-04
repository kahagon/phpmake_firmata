<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata\Device;

interface Query {

    public function request(Device $device);

    public function receive(Device $device);
}
