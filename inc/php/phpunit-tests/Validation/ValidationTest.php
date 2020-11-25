<?php
class ValidationTest extends PHPUnit_Framework_TestCase{

	public static function setUpBeforeClass(){
		require('C:/server/data/ApacheData/dev.local/inc/php/classes/Validation.php');
		require('C:/server/data/ApacheData/dev.local/Agile/Classes/AgileUserMessageException.php');
		require('C:/server/data/ApacheData/dev.local/inc/php/classes/FweUserMessageException.php');
		require('C:/server/data/ApacheData/dev.local/inc/php/classes/MaxSqlInt.php');
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Validation error, test does not exist: test_invalidTest
	 */
	public function testTestNotExists() {
		Validation::validateData(array("value"=>"invalidTest"), array("value"=>1));
	}

	public function testNoTests() {
		$data = Validation::validateData(array("value"=>array("tests")), array("value"=>1));
		$this->assertArrayHasKey("value", $data);
	}

	public function testJustValues() {
		$valid = Validation::validateData(array("value"), array("value" => 1));
		$this->assertEquals(1, $valid["value"]);
	}

	public function testDefaults() {
		$data = Validation::validateData(array("value"=>array("default"=>"default value")), array());
		$this->assertEquals("default value", $data["value"]);
	}

	public function testValidatePost() {
		$_POST['value'] = 3;
		$valid = Validation::validatePost(array("value"));
		$this->assertEquals(3, $valid["value"]);
	}

	public function testValidateGet() {
		$_GET['value'] = 5;
		$valid = Validation::validateGet(array("value"));
		$this->assertEquals(5, $valid["value"]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage value is required
	 */
	public function testDataMissingNoDefault() {
		Validation::validateData(array("value"=>array()), array());
	}

	public function testTrim() {
		$valid1 = Validation::validateData(array("allBlank" => "trim"), array("allBlank"=> " \t\r\n"));
		$valid2 = Validation::validateData(array("someText"=> "trim"), array("someText"=>" text \t\r\n"));
		$this->assertEquals("", $valid1['allBlank'], "all whitespace returns empty string");
		$this->assertEquals("text", $valid2['someText'], "all whitespace temoved around text");
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage notANumber must be numeric
	 */
	public function testNumeric() {
		$valid = Validation::validateData(array("isNumber"=>"numeric"), array("isNumber"=>"12.345"));
		$this->assertEquals("12.345", $valid['isNumber']);
		Validation::validateData(array("notANumber"=>"numeric"), array("notANumber"=>"ealpha"));
	}

	/**
	 * @depends testNumeric
	 * @expectedException Exception
	 * @expectedExceptionMessage notEqual must be 1234
	 */
	public function testNumericEqualsTo() {
		$valid = Validation::validateData(array("equal"=>
			array("tests"=>array("numeric"),
				"equalTo"=>1234)
		), array("equal"=>"1234"));
		$this->assertEquals(1234, $valid['equal']);
		Validation::validateData(array("notEqual" =>
			array("tests"=>array("numeric"),
				"equalTo"=>1234)
		), array("notEqual" => "2345"));
	}

	/**
	 * @depends testNumeric
	 * @expectedException Exception
	 * @expectedExceptionMessage equal must not be 1234
	 */
	public function testNumericNotEquals() {
		$valid = Validation::validateData(array("notEqual" =>
			array("tests"=>array("numeric"),
				"notEqualTo"=>1234)
		), array("notEqual" => "2345"));
		$this->assertNotEquals("1234", $valid['notEqual']);
		Validation::validateData(array("equal"=>
			array("tests"=>array("numeric"),
				"notEqualTo"=>1234)
		), array("equal"=>"1234"));
	}

	/**
	 * @depends testNumeric
	 * @expectedException Exception
	 * @expectedExceptionMessage equal must be less than 1234
	 */
	public function testNumericLessThan() {
		$valid = Validation::validateData(array("lessThan" =>
			array("tests"=>array("numeric"),
				"lessThan"=>1234)
		), array("lessThan" => "123"));
		$this->assertLessThan("1234", $valid['lessThan']);
		Validation::validateData(array("equal"=>
			array("tests"=>array("numeric"),
				"lessThan"=>1234)
		), array("equal"=>"1234"));
	}

	/**
	 * @depends testNumeric
	 * @expectedException Exception
	 * @expectedExceptionMessage equal must be greater than 1234
	 */
	public function testNumericGreaterThan() {
		$valid = Validation::validateData(array("greaterThan"=>
			array("tests"=>array("numeric"),
				"greaterThan"=>1234)
		), array("greaterThan"=>"12345"));
		$this->assertGreaterThan("1234", $valid['greaterThan']);
		Validation::validateData(array("equal" =>
			array("tests"=>array("numeric"),
				"greaterThan"=>1234)
		), array("equal" => "123"));
	}

	/**
	 * @depends testNumeric
	 * @expectedException Exception
	 * @expectedExceptionMessage greaterThan must be less than or equal to 1234
	 */
	public function testNumericLessEqualTo() {
		$valid = Validation::validateData(array("lessThan" =>
			array("tests"=>array("numeric"),
				"lessThanOrEqualTo"=>1234)
		), array("lessThan" => "123"));
		$this->assertLessThanOrEqual("1234", $valid['lessThan']);
		Validation::validateData(array("greaterThan"=>
			array("tests"=>array("numeric"),
				"lessThanOrEqualTo"=>1234)
		), array("greaterThan"=>"12345"));
	}

	/**
	 * @depends testNumeric
	 * @expectedException Exception
	 * @expectedExceptionMessage lessThan must be greater than or equal to 1234
	 */
	public function testNumericGreaterEqualTo() {
		$valid = Validation::validateData(array("greaterThan"=>
			array("tests"=>array("numeric"),
				"greaterThanOrEqualTo"=>1234)
		), array("greaterThan"=>"12345"));
		$this->assertGreaterThanOrEqual("1234", $valid['greaterThan']);
		Validation::validateData(array("lessThan" =>
			array("tests"=>array("numeric"),
				"greaterThanOrEqualTo"=>1234)
		), array("lessThan" => "123"));
	}

	/**
	 * @depends testNumeric
	 */
	public function testNumericOrNull() {
		$valid = Validation::validateData(array("blank"=>"numericOrNull"),array("blank"=>''));
		$this->assertNull($valid['blank']);

		$valid = Validation::validateData(array("number"=>"numericOrNull"),array("number"=>'123'));
		$this->assertNotNull($valid['number']);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage invalid must be "true" or "false"
	 */
	public function testTrueFalse() {
		$valid = Validation::validateData(array("true"=>"trueFalse", "false"=>"trueFalse"), array("true"=>"true", "false"=>"false"));
		$this->assertTrue($valid['true']);
		$this->assertFalse($valid['false']);
		Validation::validateData(array("invalid"=>"trueFalse"), array("invalid"=>"1"));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage invalid must be 1 or 0
	 */
	public function testCheckBox() {
		$valid = Validation::validateData(array("string0"=>"checkBox", "string1"=>"checkBox", "int0"=>"checkBox","int1"=>"checkBox"),
			array("string0"=>"0", "string1"=>"1", "int0"=>0, "int1"=>1));
		$this->assertEquals(0, $valid["string0"]);
		$this->assertInternalType("int", $valid["string0"]);
		$this->assertEquals(1, $valid["string1"]);
		$this->assertInternalType("int", $valid["string1"]);
		$this->assertEquals(0, $valid["int0"]);
		$this->assertEquals(1, $valid["int1"]);
		Validation::validateData(array("invalid"=>"checkBox"), array("invalid"=>true));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage cannot be blank
	 */
	public function testNotBlank() {
		$valid = Validation::validateData(array("notBlank"=>"notBlank"), array("notBlank"=>"test"));
		$this->assertEquals("test", $valid['notBlank']);
		Validation::validateData(array("blank"=>"notBlank"), array("blank"=>""));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage options parameter not set
	 */
	public function testInArrayNoOptions() {
		Validation::validateData(array("option"=>array("tests"=>array("inArray"))),array("option"=>"not an option"));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage must be one of
	 */
	public function testInArray() {
		$validValues = array("arrayMember");
		$valid = Validation::validateData(array("included"=>
			array("tests"=>array("inArray"),
				"options"=> $validValues)
		),array("included"=>"arrayMember"));
		$this->assertArraySubset(array("included"=>"arrayMember"), $valid);
		//Empty options array is fine...
		/* Validation::validateData(array("value"=>
			array("tests"=>array("inArray"),
				"options"=>array())
		), array("value"=>"something"));*/

		Validation::validateData(array("missing"=>
			array("tests"=>array("inArray"),
				"options"=> $validValues)
		),array("missing"=>"notArrayMember"));
	}

	public function testLength(){
		$valid1 = Validation::validateData(array("correctLength" =>
			array("tests"=>array("length"),
				"length"=>5)
		), array("correctLength" => "12345"));
		$valid2 = Validation::validateData(array("needsTrimming" =>
			array("tests" => array("length"),
				"length" => 5)
		), array("needsTrimming" => "  67890  "));
		$this->assertEquals("12345", $valid1['correctLength'], "simple length comparison");
		$this->assertEquals("67890", $valid2['needsTrimming'], "length match after trimming");
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp /must be 5/
	 */
	public function testLengthLong(){
		Validation::validateData(array("tooLong" =>
			array("tests" => array("length"),
				"length" => 5)
		), array("tooLong" => "1234567890"));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp /must be 5/
	 */
	public function testLengthShort() {
		Validation::validateData(array("tooShort" =>
			array("tests"=>array("length"),
				"length"=> 5
			)
		), array("tooShort" => "123"));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp /no more than/
	 */
	public function testMaxLengthLong(){
		Validation::validateData(array("tooLong" =>
			array("tests"=> array("length"),
				"maxLength"=>5
			)
		),
			array("tooLong" => "1234567890")
		);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp /at least/
	 */
	public function testMinLengthShort() {
		Validation::validateData(array("tooShort" =>
			array("tests"=> array("length"),
				"minLength"=>5
			)
		),
			array("tooShort" => "1234")
		);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage must be valid json
	 */
	public function testJsonInvalid() {
		Validation::validateData(array("invalid" =>"json"),array("invalid"=>"invalid"));
	}

	public function testJsonJustDecode() {
		$valid = Validation::validateData(array("text"=>"json"),
			array("text"=>'{"id":12345,"name":"Test","price":12.5}'));
		$this->assertEquals(['id'=>12345, 'name'=>'Test', 'price'=>12.5], $valid['text']);
	}

	public function testJsonArrayPass() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'[{"item1":"thisIsNotBlank","key2":22},{"item1":"alsoNotBlank","key2":44}]'));
		$this->assertEquals($valid['text'][0]['item1'], "thisIsNotBlank" );
		$this->assertEquals($valid['text'][0]['key2'], "22" );
		$this->assertEquals($valid['text'][1]['item1'], "alsoNotBlank" );
		$this->assertEquals($valid['text'][1]['key2'], "44" );
	}
	/**
	 * @expectedException Exception
	 */
	public function testJsonArrayFailInvalidJson() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'[x,{"item99:"thisIsNotBlank","key55":22},{"item44":"alsoNotBlank","key33":44}]vzz'));
	}

	public function testJsonArray() {
	$valid = Validation::validateData(array(
		'text' => array(
			'tests' => 'jsonArray',
		)
	),array("text"=>'[[1,2,3],[4,5,6]]'));

	$this->assertEquals($valid['text'], [[1,2,3],[4,5,6]] );
}
	/**
	 * @expectedException Exception
	 */
	public function testJsonArrayFailNotAnArray() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'{"ThisStringIsNotAnArray":"nope"}'));
	}
	/**
	 * @expectedException Exception
	 */
	public function testJsonArrayFailNotAnArrayString() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'ThisStringIsNotAnArray'));
	}
	/**
	 * @expectedException Exception
	 */
	public function testJsonArrayFailNotAnArrayWeirdString() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'"ThisStringIsNotAnArray"'));
	}
	/**
	 * @expectedException Exception
	 */
	public function testJsonArrayFailNotAnEmptyArray() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'[]'));
	}
	/**
	 * @expectedException Exception
	 */
	public function testJsonArrayFail() {
		$valid = Validation::validateData(array(
			'text' => array(
				'tests' => 'jsonArray',
				'arrayTests' => array(
					'item1',
					'key2'
				)
			)
		),array("text"=>'[{"item99":"thisIsNotBlank","key55":22},{"item44":"alsoNotBlank","key33":44}]'));
	}


	public function testJsonTests() {
		$valid = Validation::validateData(array(
			'text' => array('tests' => 'json',
				'jsonTests' => array(
					'id' => 'numeric',
					'name' => array('tests' => array('notBlank')),
					'price' => 'numeric',
				)
			)
		),array("text"=>'{"id":12345,"name":"Test","price":12.5}'));
		$this->assertEquals(12345, $valid['text']['id']);
		$this->assertEquals('Test', $valid['text']['name']);
		$this->assertEquals(12.5, $valid['text']['price']);
	}
	/**
	 * @expectedException Exception
	 */
	public function testJsonTestsFail() {
		$valid = Validation::validateData(array(
			'text' => array('tests' => 'json',
				'jsonTests' => array(
					'id' => 'numeric',
					'name' => array('tests' => array('notBlank')),
					'price' => 'numeric',
				)
			)
		),array("text"=>'{"id":"zzz","name":"","price":"xxx"}'));
	}

	public function testEmailPass() {
		$valid = Validation::validateData(array(
			'email' => 'email'
		),array("email"=>'andrewRocks@fweco.net'));
		$this->assertEquals($valid['email'], 'andrewRocks@fweco.net');
	}
	/**
	 * @expectedException Exception
	 */
	public function testEmailFail() {
		$valid = Validation::validateData(array(
			'email' => 'email'
		),array("email"=>'andrewDoesntRock@andrewDoesntRock'));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage must be 5 or 10 characters
	 */
	public function testJobLength() {
		$valid = Validation::validateData(array("jobShort"=>"jobLength", "jobFull"=>"jobLength"),
			array("jobShort"=>"12345", "jobFull"=>"67890-0000"));
		$this->assertEquals("12345-0000", $valid['jobShort']);
		$this->assertEquals("67890-0000", $valid['jobFull']);
		Validation::validateData(array("invalidJob"=>"jobLength"), array("invalidJob"=>"1234"));
	}

	public function testOptional() {
		//sanity check
		$valid = Validation::validateData(array(
			"optionalParam"=>array("optional"=>true),
			"requiredParam"
		),array(
			"optionalParam"=>"thisValueDoesntMatter",
			"requiredParam" => "yep"
		));
		$this->assertTrue(isset($valid['optionalParam']));

		//test optional
		$valid = Validation::validateData(array(
			"optionalParam"=>array("optional"=>true),
			"requiredParam"
		),array(
			"requiredParam" => "yep"
		));

		$this->assertFalse(isset($valid['optionalParam']));
		$this->assertTrue(isset($valid['requiredParam']));

	}
	
	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Test is missing prefix config with array of allowed prefixes
	 */
	public function testBarcodeNoPrefixConfig(){
		Validation::validateData([
			"barcode" => [
				'tests' => 'barcode'
			]
		],[
			'barcode' => '001123'
		]);
	}

	public function badBarcodes(){
		return [
			['','invalid length'],
			['1','invalid length'],
			['11','invalid length'],
			['111','invalid length'],
			['1111','did not have valid prefix'],
			['001.','barcode not numeric'],
			['0011.0','barcode not numeric'],
			['001ABCD','barcode not numeric'],
			['001-10','must be > 0 and <'],
			['0010','must be > 0 and <'],
			['0012147483647','must be > 0 and <']
		];
	}
		
	/**
	 * @dataProvider badBarcodes
	 */
	public function testBarcodeErrors($barcode,$expectedException){
		$this->setExpectedException(
			'Exception', $expectedException
		);
		Validation::validateData([
			"barcode" => [
				'tests' => 'barcode',
				'prefix' => ['001']
			]
		],[
			'barcode' => $barcode
		]);
	}

	public function barcodes(){
		return [
			['001123',['001'],'001','123'],
			['0151423098',['015'],'015','1423098'],
			['0151423098',['016','015'],'015','1423098'],
			['0151423098',['015','016'],'015','1423098'],
		];
	}

	/**
	 * @dataProvider barcodes
	 */
	public function testBarcode($barcode,$prefix,$expectedPrefix,$expectedValue){
		$results = Validation::validateData([
			"barcode" => [
				'tests' => 'barcode',
				'prefix' => $prefix
			]
		],[
			'barcode' => $barcode
		]);
		$this->assertEquals($results['barcode']['barcodePrefix'],$expectedPrefix);
		$this->assertEquals($results['barcode']['barcodeValue'],$expectedValue);
		$this->assertEquals($results['barcode']['barcode'],$barcode);
	}

	function badSqlInts(){
		return [
			[2147483648],
			['2147483648'],
			[9999999999999],
			[-2147483649]
		];
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage must be less than
	 * @dataProvider badSqlInts
	 */
	public function testSqlMaxIntError($int){
		Validation::validateData([
			"badInt" => "sqlmaxint"
		],[
			'badInt' => $int
		]);
	}

	function goodSqlInts(){
		return [
			[123],
			['123'],
			[0151423098],
			[0]
		];
	}
	
	/**
	* @dataProvider goodSqlInts
	*/
	public function testSqlMaxInt($int){
		$results = Validation::validateData([
			"goodInt" => "sqlmaxint"
		],[
			'goodInt' => $int
		]);
		$this->assertEquals($results['goodInt'],$int);
	}

	function badYmdDates(){
		return [
			['2019'],
			['2019-06'],
			['201906'],
			['20190627'],
			['2019/06/27'],
			['06-27-2019']
		];
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage is not a valid Y-M-D date
	 * @dataProvider badYmdDates
	 */
	public function testDateYndError($date){
		Validation::validateData([
			"badDate" => "dateYmd"
		],[
			'badDate' => $date
		]);
	}

	function goodYmdDates(){
		return [
			['2018-07-08'],
			['2018-7-8']
		];
	}
	
	/**
	* @dataProvider goodYmdDates
	*/
	public function testDateYnd($date){
		$results = Validation::validateData([
			"goodDate" => "dateYmd"
		],[
			'goodDate' => $date
		]);
		$this->assertEquals($results['goodDate'],$date);
	}

	function badIdArray(){
		return [
			['2019'],
			[['a','b','c']],
			[[1,2,3,-2147483649]]
		];
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage is not an array of ints
	 * @dataProvider badIdArray
	 */
	public function testArrayOfIdsError($idArray){
		Validation::validateData([
			"bad" => "arrayOfIds"
		],[
			'bad' => $idArray
		]);
	}

	function goodIdArray(){
		return [
			[[1,2,3,4,5,6,7,8,9,0]],
		];
	}
	
	/**
	* @dataProvider goodIdArray
	*/
	public function testArrayOfIds($idArray){
		$results = Validation::validateData([
			"good" => "arrayOfIds"
		],[
			'good' => $idArray
		]);
		$this->assertEquals($results['good'],$idArray);
	}

	public function integers(){
		return [
			//Good checks
			[1,[],true,null],
			[1,['equalTo' => 1],true,null],
			[1,['notEqualTo' => 2],true,null],
			[1,['lessThan' => 3],true,null],
			[4,['greaterThan' => 3],true,null],
			[1,['lessThanOrEqualTo' => 3],true,null],
			[3,['lessThanOrEqualTo' => 3],true,null],
			[4,['greaterThanOrEqualTo' => 3],true,null],
			[3,['greaterThanOrEqualTo' => 3],true,null],

			//Exception Checks
			['abc',[],false,'integer must be integer'],
			[1,['equalTo' => 3],false,'integer must be 3'],
			[2,['notEqualTo' => 2],false,'integer must not be 2'],
			[4,['lessThan' => 3],false,'integer must be less than 3'],
			[1,['greaterThan' => 3],false,'integer must be greater than 3'],
			[4,['lessThanOrEqualTo' => 3],false,'integer must be less than or equal to 3'],
			[1,['greaterThanOrEqualTo' => 3],false,'integer must be greater than or equal to 3']
		];
	}
		
	/**
	 * @dataProvider integers
	 */
	public function testInteger($integer,$rules,$valid,$expectedException){
		$params = [
			'integer' => [
				'tests' => 'integer'
			]
		];
		foreach($rules as $rule => $ruleValue){
			$params['integer'][$rule] = $ruleValue;
		}
		if(!$valid){
			$this->setExpectedException(
				'Exception', $expectedException
			);
		}
		$results = Validation::validateData($params,[
			'integer' => $integer
		]);
		$this->assertEquals($results['integer'],$integer);
	}

	public function testPageStart(){
		$results = Validation::validateData([
			"good" => "pageStart"
		],[
			'good' => 0
		]);
		$this->assertEquals($results['good'],0);
		$this->setExpectedException(
			'Exception','bad must be greater than or equal to 0'
		);
		$results = Validation::validateData([
			"bad" => "pageStart"
		],[
			'bad' => -1
		]);
	}

	public function testPageLimit(){
		$results = Validation::validateData([
			"good" => "pageLimit"
		],[
			'good' => 3
		]);
		$this->assertEquals($results['good'],3);
		$this->setExpectedException(
			'Exception','bad must be greater than or equal to 1'
		);
		$results = Validation::validateData([
			"bad" => "pageLimit"
		],[
			'bad' => 0
		]);
	}

}
