<?php

use Agile\AgileLibrary;

class Email extends AgileLibrary{

	private static $config;
	private static function getDevEmail(){
		if(isset(self::$agileApp->systemConfigs['adminEmail'] )){
			return self::$agileApp->systemConfigs['adminEmail'];
		}
		throw new Exception("SystemConfig 'adminEmail' is missing");
	}

	public static function setConfig($key, $value){
		self::$config[$key] = $value;
	}

	public static function setConfigArray($config){
		$defaults = array(
			'to' => null,
			'cc' => null,
			'bcc' => null,
			'headers' => null,
			'from' => 'Swift Reptiles',
			'fromEmail' => 'webmaster@leesheet.com',
			'subject' => 'subject',
			'message' => 'message',
			'showSourceFooter' => true
		);
		self::$config = $config;
		foreach($defaults as $key => $default){
			if(!isset(self::$config[$key])){
				self::$config[$key] = $default;
			}
		}
	}

	public static function send($config = null){
		if($config !== null){
			self::setConfigArray($config);
		}

		//do we have the minimum configs to send an email?
		if(self::$config['to'] === null || self::$config['message'] === null){
			$ex = new Exception("Invalid config for sending email! Missing 'to' or 'message'");
			$ex->exceptionData = self::$config;
			throw $ex;
		}

		if(Dev::isDev()) {
			self::$config['to'] = Email::getDevEmail();
			self::$config['cc'] = null;
			self::$config['bcc'] = null;
		}

		self::implodeIfArray(self::$config['to'], ",");
		self::implodeIfArray(self::$config['cc'], ",");
		self::implodeIfArray(self::$config['bcc'], ",");

		if (self::$config['headers'] === null) {
			self::$config['headers'] = self::generateHeaders();
		}

		self::implodeIfArray(self::$config['message'], "\r\n");

		if(true === self::$config['showSourceFooter']){
			//call debug_backtrace here so we can get the line send() was called from
			$debugBacktrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT ,2);
			if(isset(self::$config['debugBacktrace'])){
				$debugBacktrace = self::$config['debugBacktrace'];
			}
			self::$config['message'] .= self::generateDebugFooter($debugBacktrace);
		}

		if(FALSE === mail(self::$config['to'], self::$config['subject'], self::$config['message'], self::$config['headers']) ){
			$ex = new Exception('Error sending email!');
			$ex->exceptionData = self::$config;
			throw $ex;
		}
	}

	private static function implodeIfArray(&$stringOrArray, $separator){
		if(is_array($stringOrArray)) {
			$stringOrArray = implode($separator, $stringOrArray);
		}
	}

	private static function generateHeaders(){
		$headers = array();
		$headers[] = "From: ".self::$config['from']." <".self::$config['fromEmail'].">";

		if(self::$config['cc'] !== null && trim(self::$config['cc']) !== ''){
			$headers[] = "Cc: ".self::$config['cc'];
		}
		if(self::$config['bcc'] !== null && trim(self::$config['bcc']) !== ''){
			$headers[] = "Bcc: ".self::$config['bcc'];
		}
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-Type: text/html; charset=ISO-8859-1";
		$headers[] = "Reply-To: Swift Reptiles <webmaster@leesheet.com>";
		$headers[] = "Return-Path: Swift Reptiles <webmaster@leesheet.com>";
		$headers[] = "Organization: Shwift Reptiles";
		return implode("\r\n", $headers);
	}

	private static function generateDebugFooter($backtrace){

		$backtraceStr = '';
		if(isset($backtrace[1])){
			if( isset($backtrace[1]['class']) ){
				$backtraceStr .= ' '.$backtrace[1]['class'];
			}elseif( isset($backtrace[1]['file']) ){
				$backtraceStr .= ' '.$backtrace[1]['file'];
			}
			if(isset($backtrace[1]['function'])){
				$backtraceStr .= '/'.$backtrace[1]['function'];
			}
		}
		if(isset($backtrace[0]) && isset($backtrace[0]['line'])){
			$backtraceStr .= ':'.$backtrace[0]['line'];
		}

		//return "<BR/>\r\n<BR/>\r\nSent at ".date('Y-m-d g:ia').' from '.$_SERVER['SERVER_NAME']."<BR\>\r\n<span style=\"color:#BBB\">{$backtraceStr}</span>";
	}
}