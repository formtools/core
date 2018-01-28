<?php

namespace FormTools\Tests;

use PHPUnit\Framework\TestCase;
use FormTools\General;


class GeneralTest extends TestCase
{

    // General::trimString

    public function testTrimString_AddsDefaultEllipsis()
    {
        $string = "abcdefghijklmnop";
        $trimmed_string = General::trimString($string, 5);
        $this->assertEquals($trimmed_string, "abcde...");
    }

    public function testTrimString_ShorterStringThanSpecifiedTruncationLength()
    {
        $string = "abcdefghijklmnop";
        $trimmed_string = General::trimString($string, 50);
        $this->assertEquals($trimmed_string, $string);
    }

    public function testTrimString_ChunkedIntoLinesWithPageBreaks()
    {
        $string = "abcdefghijklmnop";
        $trimmed_string = General::trimString($string, 4, "page_break");
        $this->assertEquals($trimmed_string, "abcd<br />efgh<br />ijkl<br />mnop");
    }


    // General::stripChars

    public function testStripChars_DefaultWhitelist()
    {
        $string = "All spaces and non-alpha, plus numeric chars (like 1-9) should be stripped out!";
        $trimmed_string = General::stripChars($string);
        $this->assertEquals($trimmed_string, "Allspacesandnonalphaplusnumericcharslike19shouldbestrippedout");
    }

    public function testStripChars_DefaultWhitelistTestAllCharsRemoved()
    {
        $string = "!@#$%^&*()";
        $trimmed_string = General::stripChars($string);
        $this->assertEquals($trimmed_string, "");
    }

    public function testStripChars_CustomWhitelist()
    {
        $string = "1234567890";
        $trimmed_string = General::stripChars($string, "97531");
        $this->assertEquals($trimmed_string, "13579");
    }
}
