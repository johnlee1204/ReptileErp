<?php

namespace Agile;

use Exception;
use sqlsrv_helper;

abstract class AgileTable extends AgileLibrary implements AgileTableInterface {

	static function debugGenerateSqlTable(){
		/** @var AgileTable $class */
		$class = get_called_class();
		$tableData = $class::readTable();
		$columns = $class::readColumns();
		$sqlColumnArray = array();
		foreach($columns as $agileName => $column){
			$sqlColumn = $agileName;
			if(isset($column[Column::COLUMN])){
				$sqlColumn = $column[Column::COLUMN];
			}
			$sqlType = 'varchar';
			if(isset($column[Column::TYPE])){
				$sqlType = $column[Column::TYPE];
			}
			$sqlSize = '';
			if($sqlType == 'varchar'){
				$sqlSize = 'MAX';
				if(isset($column[Column::MAX_LENGTH])){
					$sqlSize = $column[Column::MAX_LENGTH];
				}
				$sqlSize = '('.$sqlSize.')';
			}
			$sqlColumn = '['.$sqlColumn.']';
			$sqlType = '['.$sqlType.']';
			$sqlColumnArray[] = $sqlColumn.' '.$sqlType.$sqlSize.' NULL';
		}
		$sql = array();
		$sql[] = "CREATE TABLE [{$tableData['database']}].[{$tableData['table']}](";
		$sql[] = implode(",\r\n",$sqlColumnArray);
		$sql[] = ')';
		return implode("\r\n",$sql);
	}

	/**
	 * Utility output function for columns
	 * can be used like so in column config:
	 * Column::FORMAT_FUNCTION => function($value){return self::formatJsonOutput($value);}
	 *
	 *
	 * @param $value
	 * @return mixed
	 */
	public static function formatJsonOutput($value){
		if(strlen($value) > 0 && NULL !== $json = json_decode($value,true)){
			return print_r($json,true);
		}
		return $value;
	}

	/**
	 * @param sqlsrv_helper $dbc
	 * @param $data
	 * @throws Exception
	 */
	static function insert($dbc, $data){
		/** @var AgileTable $class */
		$class = get_called_class();
		$tableData = $class::readTable();
		$columnData = $class::readColumns();
		$table = $tableData['database'].'.'.$tableData['table'];
		$insertData = array();
		foreach($data as $key => $value){
			if(isset($columnData[$key]) && isset($columnData[$key][Column::COLUMN])){
				//Automatically encode a column value to json if it has a json flag and it's still an array.
				if(isset($columnData[$key][Column::JSON]) && $columnData[$key][Column::JSON] && is_array($value)){
					$value = json_encode($value);
				}
				$insertData[$columnData[$key][Column::COLUMN]] = $value;
			}
		}
		$dbc->insert($table,$insertData);
	}
}