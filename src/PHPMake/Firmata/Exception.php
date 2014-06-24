<?php
namespace PHPMake\Firmata;
use PHPMake\Firmata;

class Exception extends \Exception {

    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        Firmata::getLogger()
            ->error(sprintf("Firmata Exception occurred. %s", $message));
    }

}
