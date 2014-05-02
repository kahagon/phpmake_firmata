<?php
namespace PHPMake;

class Firmata {
    private static $_logger;

    public static function setLogger(\Psr\Log\LoggerInterface $logger) {
        self::$_logger = $logger;
    }

    public static function getLogger() {
        if (!self::$_logger) {
            self::$_logger = new \PHPMake\Logger();
            self::$_logger->setThreshold(\Psr\Log\LogLevel::ERROR);
        }

        return self::$_logger;
    }

    public static function modeStringFromCode($mode) {
        switch ($mode) {
        case self::INPUT:
            return 'input';
        case self::OUTPUT:
            return 'output';
        case self::ANALOG:
            return 'analog';
        case self::PWM:
            return 'pwm';
        case self::SERVO:
            return 'servo';
        case self::SHIFT:
            return 'shift';
        case self::I2C:
            return 'i2c';
        default:
            return 'unknown';
        }
    }

    const HIGH = 1;
    const LOW = 0;

    const MESSAGE_ANALOG = 0xE0;
    const MESSAGE_DIGITAL = 0x90;
    const REPORT_ANALOG = 0xC0;
    const REPORT_DIGITAL = 0xD0;

    const SYSEX_START = 0xF0;
    const SYSEX_END = 0xF7;
    const QUERY_FIRMWARE = 0x79;
    const REPORT_VERSION = 0xF9;
    const QUERY_CAPABILITY = 0x6B;
    const RESPONSE_CAPABILITY = 0x6C;
    const QUERY_PIN_STATE = 0x6D;
    const RESPONSE_PIN_STATE = 0x6E;
    const EXTENDED_ANALOG = 0x6F;
    const QUERY_ANALOG_MAPPING = 0x69;
    const RESPONSE_ANALOG_MAPPING = 0x6A;
    const SAMPLING_INTERVAL = 0x7A;

    const SET_PIN_MODE = 0xF4;

    const INPUT = 0x00;
    const OUTPUT = 0x01;
    const ANALOG = 0x02;
    const PWM = 0x03;
    const SERVO = 0x04;
    const SHIFT = 0x05;
    const I2C = 0x06;

    const ANY_BYTE = '*';

}
