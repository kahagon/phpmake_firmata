<?php
namespace PHPMake;

class Firmata {
    
    const SYSEX_START = 0xF0;
    const SYSEX_END = 0xF7;
    const QUERY_FIRMWARE = 0x79;
    const REPORT_VERSION = 0xF9;
    const QUERY_CAPABILITY = 0x6B;
    const RESPONSE_CAPABILITY = 0x6C;
}
