<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata;

interface Query {

    public function request(Firmata\Stream $stream);

    public function receive(Firmata\Stream $stream);
}
