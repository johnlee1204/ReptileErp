<?php

namespace Libraries;

use Dev;
use Exception;

class Curl {

	static $lastCurlError = false;

	private static function generateHeaders($headers){
		$headerParam = [];
		foreach($headers as $key => $value){
			$headerParam[] = $key.': '.$value;
		}
		return $headerParam;
	}

	private static function execCurl($ch,$url){
		$server_output = curl_exec($ch);
		$curl_error = curl_error($ch);

		if($server_output === false){
			throw new Exception("Failed to curl to '".$url."' ".$curl_error);
		}

		//TODO- add config to disable this check in the event we get 300 returns (like constant contact api)
//		$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
//		if($responseCode !== 200){
//			throw new Exception('Curl Response Code not 200! Code returned: '.$responseCode);
//		}

		if(trim($server_output) === ''){
			self::$lastCurlError = "Failed to curl to '".$url."' ".$curl_error;
			return false;
		}

		if(null === $data = json_decode($server_output,true)){
			self::$lastCurlError = "Failed to decode response from '".$url."' '".$server_output."'";
			return false;
		}

		return $data;
	}

	static function postJson($url, $postData = [], $headers = []) {
		$jsonData = json_encode($postData);

		$headers = array_merge([
			'Content-Type' => 'application/json',
			'Content-Length' => strlen($jsonData)
		],$headers);
		return self::post($url,$jsonData,$headers);
	}

	static function sendJsonRequest($verb, $url, $postData = [], $headers = []) {
		$jsonData = json_encode($postData);

		$headers = array_merge([
			'Content-Type' => 'application/json',
			'Content-Length' => strlen($jsonData)
		],$headers);
		return self::sendRequest($verb, $url,$jsonData,$headers);
	}

	static function sendRequest($verb, $url, $postData = [], $headers = []){
		$headerParam = self::generateHeaders($headers);
		self::$lastCurlError = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$headerParam);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,5);
		curl_setopt($ch,CURLOPT_TIMEOUT, 10);

		if(Dev::isDev()){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}

		$data = self::execCurl($ch,$url);
		curl_close ($ch);
		return $data;
	}
	static function post($url, $postData = [], $headers = []){
		$headerParam = self::generateHeaders($headers);
		self::$lastCurlError = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$headerParam);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,5);
		curl_setopt($ch,CURLOPT_TIMEOUT, 10);

		if(Dev::isDev()){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}

		$data = self::execCurl($ch,$url);
		curl_close ($ch);
		return $data;
	}

	static function get($url, $headers = []){

		$headerParam = self::generateHeaders(array_merge([
			'Content-Type' => 'application/json',
		],$headers));

		self::$lastCurlError = false;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$headerParam);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,5);
		curl_setopt($ch,CURLOPT_TIMEOUT, 10);

//		if(Dev::isDev()){
//			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//		}

		$data = self::execCurl($ch,$url);
		curl_close ($ch);

		return $data;
	}
}