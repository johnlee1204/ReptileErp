<?php

class FormValidationTest extends PHPUnit_Framework_TestCase
{
	var $validator;

	public static function setUpBeforeClass(){
		require('C:/server/data/apacheData/dev.local/inc/php/classes/FormValidation.php');
	}

	function setUp(){
		$this->validator = new FormValidation();
	}

	function testSetRules(){
		$_POST['setTest'] = 'true';

		$this->validator->set_rules('');
		$this->assertEmpty($this->validator->get_field_data());


		$this->validator->set_rules('test', '');
		$this->assertEquals(count($this->validator->get_field_data()), 1);

		$this->validator->set_rules('test', '', 'required');
		$this->assertEquals(count($this->validator->get_field_data()),1);

		$testConfig = array(
			array(
				"field"=>"test1",
				"label"=>"Test1",
				"rules"=>"required"
			), array(
				"field"=>"test2",
				"label"=>"Test2",
				"rules"=>"trim"
			)
		);
		$this->validator->set_rules($testConfig);
		$this->assertEquals(count($this->validator->get_field_data()),3);
	}

	function testRequired(){
		$this->validator->set_rules('reqField','required');
		$this->assertFalse($this->validator->run(), "Missing required field was allowed");

		$this->validator->set_rules('reqField','required');
		$_POST['reqField'] = '';
		$this->assertFalse($this->validator->run(), "Empty string allowed for required");

		$this->validator->set_rules('reqField','required');
		$_POST['reqField'] = 'something';
		$this->assertTrue($this->validator->run(), "Existant field considered missing by required");
	}

	function testRegexMatch(){
		$_POST['regexTest'] = '1aA- ';
		$this->validator->set_rules('regexTest', 'regex_match[/[a-z]/]');
		$this->assertTrue($this->validator->run(), "Alpha regex fails");

		$this->validator->set_rules('regexTest', 'regex_match[/[0-9]/]');
		$this->assertTrue($this->validator->run(), "Numeric regex fails");

		$this->validator->set_rules('regexTest', 'regex_match[/[0-9]/]');
		$_POST['regexTest'] = 'Aa- ';
		$this->assertFalse($this->validator->run(), "Numeric test allows alpha");
	}

	function testTrim(){
		$_POST['trimTest'] = "  ";
		$this->validator->set_rules('trimTest', 'trim');
		$this->validator->run();
		$this->assertEquals('', $_POST['trimTest'], "Whitespace not removed by trim");

		$_POST['trimTest'] = " a ";
		$this->validator->set_rules('trimTest', 'trim');
		$this->validator->run();
		$this->assertEquals('a', $_POST['trimTest'],"Whitespace not removed by trim");
	}

	function testMatches()
	{
		$_POST['match1'] = "same";
		$_POST['match2'] = "same";

		$this->validator->set_rules('match1', 'matches[match2]');
		$this->assertTrue($this->validator->run(), "Identical text doesn't match");

		$_POST['match3'] = 'different';
		$this->validator->set_rules('match2', 'matches[match3]');
		$this->assertFalse($this->validator->run(), "Different strings shouldn't match");
	}

	public function testMinLength()
	{
		$_POST['minLength'] = "123";
		$this->validator->set_rules('minLength', 'min_length[2]');
		$this->assertTrue($this->validator->run(),"String of length 3 considered shorter than 2");

		$this->validator->set_rules('minLength', 'min_length[4]');
		$this->assertFalse($this->validator->run(), "String of length 3 considered longer than 4");
	}

	public function testMaxLength()
	{
		$_POST['maxLength'] = "123";
		$this->validator->set_rules('maxLength', 'max_length[2]');
		$this->assertFalse($this->validator->run(),"String of length 3 considered shorter than 2");

		$this->validator->set_rules('maxLength', 'max_length[4]');
		$this->assertTrue($this->validator->run(),"String of length 3 considered longer than 4");
	}

	public function testAlpha(){
		$_POST['alphaTest'] = "123";
		$this->validator->set_rules('alphaTest', 'alpha');
		$this->assertFalse($this->validator->run(), "Numeric passed as alpha");

		$_POST['alphaTest'] = "abcABC";
		$this->validator->set_rules('alphaTest', 'alpha');
		$this->assertTrue($this->validator->run(), "alpha not passing as alpha");
	}

	public function testAlphaNumeric(){
		$_POST['alphaNumTest'] = "abcABC123";
		$this->validator->set_rules('alphaNumTest', 'alpha_numeric');
		$this->assertTrue($this->validator->run(), "Alphanumeric is not alphanumeric");

		$_POST['alphaNumTest'] = "abcABC123._-";
		$this->validator->set_rules('alphaNumTest', 'alpha_numeric');
		$this->assertFalse($this->validator->run(), "Dots and dashes are alphanumeric");

	}

	public function testInteger(){
		$_POST['integerTest'] = "+0123";
		$this->validator->set_rules('integerTest', 'integer');
		$this->assertTrue($this->validator->run(), "Integers are not integers");

		$_POST['integerTest'] = "123.532";
		$this->validator->set_rules('integerTest', 'integer');
		$this->assertFalse($this->validator->run(), "Floats are integers");
	}

	public function testAlphaDash(){
		$_POST['alphaDashTest'] = "abcABC123-_";
		$this->validator->set_rules('alphaDashTest', 'alpha_dash');
		$this->assertTrue($this->validator->run(), "Alphadash not alphadash");

		$_POST['alphaDashTest'] = " #$%^&<>";
		$this->validator->set_rules('alphaDashTest', 'alpha_dash');
		$this->assertFalse($this->validator->run(), "Non-alphadash treated as alphadash");
	}

	public function testNative(){
		$_POST['nativeTest'] = "abcABC123-_";
		$this->validator->set_rules('nativeTest', 'sha1');
		$this->validator->run();
		$this->assertEquals(strlen($_POST['nativeTest']),40, "SHA1 not 40 chars long");
	}

	/*
	 * @depends testTrim
	 * @depends testMinLength
	 * @depends testMaxLength
	 * @depends testNative
	 */
	public function testRuleChain(){
		$_POST['chainedRuleTest'] = "  abcABC123-_  ";
		$this->validator->set_rules('chainedRuleTest', 'trim|sha1|min_length[40]|max_length[40]');
		$this->assertTrue($this->validator->run(),"Chained tests fail");
	}

	/*
	 * @depends testTrim
	 * @depends testRequired
	 */
	public function testSetData()
	{
		$data_array = array("setDataTest"=>' abcdefg ');
		$this->validator->set_data($data_array);
		$this->validator->set_rules('setDataTest','trim|required');
		$this->assertTrue($this->validator->run(), "Custom set data not used");
		$this->validator->set_rules('missingData','trim|required');
		$this->assertFalse($this->validator->run(), "Missing data found!?!");
	}
}

