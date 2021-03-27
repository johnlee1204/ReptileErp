<?php
class AgileModel {
	/* @var AgileApp */
	public static $agileApp;
	/* @var mysql_helper */
	public static $database;
	public static $databaseFwe;

	/**
	 * Assign AgileApp static via autoloader
	 * @param AgileApp $agileApp
	 */
	public static function assignAgileApp($agileApp){
		AgileModel::$agileApp = $agileApp;
		AgileModel::$database = $agileApp->database;

	}
}