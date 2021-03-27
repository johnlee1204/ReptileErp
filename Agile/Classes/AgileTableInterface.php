<?php

namespace Agile;

interface AgileTableInterface {
	/**
	 * @return array
	 */
	static function readTable();

	/**
	 * @return array
	 */
	static function readColumns();

}