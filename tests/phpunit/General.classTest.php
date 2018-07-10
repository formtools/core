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


    // General::isVersionEarlierThan()

    public function testIsVersionEarlierThan_TestSameVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.0.0", "1.0.0"), false);
    }

    public function testIsVersionEarlierThan_TestLaterVersionBugVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.0.1", "1.0.0"), false);
    }

    public function testIsVersionEarlierThan_TestEarlierVersionBugVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.0.0", "1.0.5"), true);
    }

    public function testIsVersionEarlierThan_TestLaterVersionMinorVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.4.9", "1.4.5"), false);
    }

    public function testIsVersionEarlierThan_TestEarlierVersionMinorVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.3.5", "1.3.9"), true);
    }

    public function testIsVersionEarlierThan_TestEarlierVersionMajorVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("2.0.0", "1.9.9"), false);
    }

    public function testIsVersionEarlierThan_TestLaterVersionMajorVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("2.0.0", "3.0.0"), true);
    }

    public function testIsVersionEarlierThan_TestLaterMultiCharBugVersion()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.0.99", "1.0.0"), false);
    }

    public function testIsVersionEarlierThan_TestEarlierMultiCharBugVersion1()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.0.9", "1.0.10"), true);
    }

    public function testIsVersionEarlierThan_TestEarlierMultiCharBugVersion2()
    {
        $this->assertEquals(General::isVersionEarlierThan("1.0.9", "1.0.888"), true);
    }

    public function testIsVersionEarlierThan_TestZeroAsSecondNumber()
    {
        $this->assertEquals(General::isVersionEarlierThan("40.0.4", "4.0.8"), false);
    }

    // General::arrayRemoveByValue
	public function testArrayRemoveByValueRemovesSingleValue() {
    	$data = array(
    		"why",
			"hello",
			"world"
		);
		$this->assertEquals(General::arrayRemoveByValue($data, "hello"), array(
			"why", "world"
		));
	}

	public function testArrayRemoveByValueRemovesSingleValueIsCaseSensitive() {
		$data = array(
			"why",
			"hello",
			"world"
		);
		$this->assertEquals(General::arrayRemoveByValue($data, "Hello"), array(
			"why", "hello", "world"
		));
	}
}

