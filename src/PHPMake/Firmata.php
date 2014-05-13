<?php
/**
 * Firmata is a protocol for controlling device like Arduino from host machine.
 * \PHPMake\Firmata implements Firmata as host machine side.
 * This API hides protocol details and provides object-oriented interface.
 * Details of Firmata Protocol, see Firmata homepage({@link http://firmata.org/wiki/Main_Page}).
 *
 * @see http://firmata.org/wiki/Main_Page Firmata homepage
 * @author oasynnoum <k.ahagon@n-3.so>
 */

namespace PHPMake;

/**
 * Firmata utility class
 *
 * This class contains common method and constants.
 */
class Firmata {
    private static $_logger;

    /**
     * Set logger for this Firmata framework internally logging.
     * If you want to set customized logger, 
     * this method should be called at first, before calling any other methods.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @return void
     */
    public static function setLogger(\Psr\Log\LoggerInterface $logger) {
        self::$_logger = $logger;
    }

    /**
     * Return LoggerInterface.
     * If not called setLogger() before this method, return instance of \PHPMake\Logger.
     *
     * @return \Psr\Log\LoggerInterface 
     */
    public static function getLogger() {
        if (!self::$_logger) {
            self::$_logger = new \PHPMake\Logger();
            self::$_logger->setThreshold(\Psr\Log\LogLevel::ERROR);
        }

        return self::$_logger;
    }

    /**
     * Convert integer to string which specify pin mode.
     *
     * @param int $mode
     * @return string
     */
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

    /**
     * High voltage.
     */
    const HIGH = 1;

    /**
     * Low voltage.
     */
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

    /**
     * Digital input pin mode.
     */
    const INPUT = 0x00;

    /**
     * Digital write pin mode.
     */
    const OUTPUT = 0x01;

    /**
     * Analog read pin mode.
     */
    const ANALOG = 0x02;

    /**
     * PWM(analog write) pin mode.
     */
    const PWM = 0x03;

    /**
     * Servo pin mode.
     */
    const SERVO = 0x04;

    /**
     *
     */
    const SHIFT = 0x05;

    /**
     * I2C pin mode.
     */
    const I2C = 0x06;

    const ANY_BYTE = '*';

}
