<?php
namespace PHPMake;

class Firmata {
    
    const SYSEX_START = 0xF0;
    const SYSEX_END = 0xF7;
    const QUERY_FIRMWARE = 0x79;
    const QUERY_VERSION = 0xF9;
    const REPORT_VERSION = 0xF9;
    const QUERY_CAPABILITY = 0x6B;
    const RESPONSE_CAPABILITY = 0x6C;

    const INPUT = 0x00;
    const OUTPUT = 0x01;
    const ANALOG = 0x02;
    const PWM = 0x03;
    const SERVO = 0x04;
    const SHIFT = 0x05;
    const I2C = 0x06;
    
}
