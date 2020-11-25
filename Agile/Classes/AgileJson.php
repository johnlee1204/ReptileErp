<?php

class AgileJson
{
	static function utf8EncodeString($string)
	{
//		if (mb_detect_encoding($data, "UTF-8", true) === "UTF-8") {
//			return $data;
//		}
//		return utf8_encode($data);

//iconv is faster with more predictable behavior with unknown characters via IGNORE
		$string = iconv('ISO-8859-1', 'UTF-8//IGNORE', $string);
		if (FALSE === $string) {
			throw new Exception("Failed to iconv convert text to UTF-8. Text: " . $string);
		}
		return $string;
	}

	static function utf8EncodeAll($data)
	{
		switch (gettype($data)) {
			case 'string':
				return self::utf8EncodeString($data);
			case 'array':
				$convertedOutput = array();
				foreach ($data as $key => $value) {
					$convertedOutput[$key] = self::utf8EncodeAll($value);
				}
				return $convertedOutput;
		}
		return $data;
	}

	static function encode($data)
	{
		$outputJson = json_encode(self::utf8EncodeAll($data));
		if ($outputJson === FALSE) {
			throw new Exception("json_encode failed. error: " . json_last_error_msg());
		}

		return $outputJson;

	}
}