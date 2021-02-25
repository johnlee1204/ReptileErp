<?php

namespace Agile;

use AgileApp;

class AgileLibrary{
	/* @var AgileApp */
	public static $agileApp;

	/**
	 * Assign AgileApp static via autoloader
	 * @param AgileApp $agileApp
	 */
	public static function assignAgileApp($agileApp){
		AgileLibrary::$agileApp = $agileApp;
	}
}