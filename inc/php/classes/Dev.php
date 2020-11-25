<?php
	class Dev{
		public static function isDev(){
			return getenv('PHPENV')==="DEV";
		}
	}