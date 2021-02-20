<?php

namespace Log\Models;
use Agile\AgileLog;
use Agile\Column;
use Exception;
use FormatModel;
use sqlsrv_helper;

class LogModel {
	/**
	 * @param sqlsrv_helper $dbc
	 * @return array
	 */
	static function readApplicationLogApps($dbc){
		$prefix = 'log_';
		$prefixLength = strlen($prefix);
		$tables = $dbc->fetch_all_assoc("SELECT TABLE_NAME AS tableName FROM AgileFweData.INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE ?+'%' ORDER BY TABLE_NAME",array($prefix));
		$apps = array();
		foreach($tables as $table){
			$apps[] = array(substr($table['tableName'],$prefixLength));
		}
		return $apps;
	}

	static function readApplicationLogMetadata($appName){
		/** @var AgileLog $logTableClass */
		$logTableClass = $appName."\\Tables\\".$appName.'Log';
		$logTableColumns = $logTableClass::readColumns();
		$outputColumns = array();
		foreach($logTableColumns as $columnName => $columnData){
			$row = array('column' => $columnName);
			$row['hidden'] = false;
			if(isset($columnData[Column::GRID_COLUMN_HIDDEN])){
				$row['hidden'] = $columnData[Column::GRID_COLUMN_HIDDEN];
			}
			if(isset($columnData[Column::GRID_COLUMN_WIDTH])){
				$row['width'] = $columnData[Column::GRID_COLUMN_WIDTH];
			}else{
				$row['width'] = 100;
			}
			if(isset($columnData[Column::LABEL])){
				$row['label'] = $columnData[Column::LABEL];
			}else{
				$row['label'] = $columnName;
			}
			$outputColumns[] = $row;
		}

		$logTableData = $logTableClass::readTable();
		$tableMetadata = [];
		$tableMetadata['comparison'] = false;
		if(isset($logTableData['comparison'])){
			$tableMetadata['comparison'] = $logTableData['comparison'];
		}
		return [
			'columns' => $outputColumns,
			'table' => $tableMetadata
		];
	}

	/**
	 * @return array
	 */
	static function readApplicationLogValidation(){
		return array(
			'appName' => array('tests' => 'notBlank'),
			'dateFrom' => array('tests' => 'notBlank','default' => '1900-01-01'),
			'dateTo' => array('tests' => 'notBlank','default' => date('Y-m-d')),
			'timeFrom' => array('tests' => 'notBlank','default' => '0:00'),
			'timeTo' => array('tests' => 'notBlank','default' => '23:59'),
			'limit' => array('tests' => 'numeric','default' => 25),
			'start' => array('tests' => 'numeric','default' => 0),
			'sort' => array('tests' => 'notBlank','default' => 'date'),
			'dir' => array('tests' => 'notBlank','default' => 'DESC'),
			'searchColumn' => array('tests' => 'trim','default' => ''),
			'searchOperation' => array('tests' => 'trim','default' => ''),
			'searchTerm' => array('tests' => 'trim','default' => '')
		);
	}

	/**
	 * @param sqlsrv_helper $dbc
	 * @param $params
	 * @return array
	 */
	static function readApplicationLog($dbc, $params){
		$appName = $params['appName'];
		$start = $params['start'];
		$limit = $params['limit'];
		$sortBy = $params['sort'];
		$sortDirection = $params['dir'];
		$dateFrom = $params['dateFrom'];
		$dateTo = $params['dateTo'];
		$timeFrom = $params['timeFrom'];
		$timeTo = $params['timeTo'];
		$searchColumn = $params['searchColumn'];
		$searchOperation = $params['searchOperation'];
		$searchTerm = $params['searchTerm'];


		//$columnResults = $dbc->fetch_all_assoc("SELECT COLUMN_NAME column FROM AgileFwe.INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'log_'+?",array($appName));
		//$columns = array();
		//foreach($columnResults as $result){
		//	$columns[] = $result['column'];
		//}

		/** @var AgileLog $logTableClass */
		$logTableClass = $appName."\\Tables\\".$appName.'Log';
		$logTableColumns = $logTableClass::readColumns();
		$logTableData = $logTableClass::readTable();
		$logTable = $logTableData['database'].'..'.$logTableData['table'];

		$outputColumns = array();
		$columns = array();
		foreach($logTableColumns as $columnName => $columnData){
			if(isset($columnData[Column::COLUMN])){
				$columns[] = '['.$columnData[Column::COLUMN].'] as '.$columnName;
				$outputColumns[] = $columnName;
			}
		}

		$whereArray = array();
		$whereValues = array();

		//Always read date & time
		$whereArray[] = 'date >= ?';
		$whereValues[] = date('Y-m-d H:i',strtotime($dateFrom.' '.$timeFrom));
		$whereArray[] = 'date <= ?';
		$whereValues[] = date('Y-m-d H:i',strtotime($dateTo.' '.$timeTo));

		if(!in_array($searchOperation,['','>','<','=','like','<>','<=','>='])){
			throw new Exception('Unknown Search Operation!');
		}

		if($searchColumn !== ''){
			$whereArray[] = $searchColumn.' '.$searchOperation.' ?';
			$whereValues[] = $searchTerm;
		}

		$whereStr = '';
		if(count($whereArray) > 0){
			$whereStr = 'WHERE ' . implode(" AND \r\n", $whereArray);
		}

		$pageEnd = $start+$limit;
		$finalSortBy = $sortBy;
		if(isset($logTableColumns[$sortBy]) && isset($logTableColumns[$sortBy][Column::COLUMN])){
			$tableSort = $logTableColumns[$sortBy][Column::COLUMN];
			if(isset($fieldModels[$sortBy]['varCharCastLength'])){
				$varCharCastLength = $logTableColumns[$sortBy]['varCharCastLength'];
				$tableSort = 'CAST('.$tableSort.' AS varchar('.$varCharCastLength.'))';
				$finalSortBy = 'CAST('.$sortBy.' AS varchar('.$varCharCastLength.'))';
			}
		}else{
			throw new Exception('Bad Sort Column '.$sortBy);
		}
		$tableSort .= " ".$sortDirection;
		$finalSortBy .= " ".$sortDirection;

		$sql = "SELECT
			".implode(",",$outputColumns)."
			FROM(
				SELECT
				".implode(",",$columns).",
				row_number() over(ORDER BY {$tableSort}) as rowNum
				FROM {$logTable}
				{$whereStr}
			) as tableToPage
			WHERE rowNum > ? and rowNum <= ? ORDER BY {$finalSortBy}
		";

		//echo $sql;
		//die();

		$totalSql = "
        select count(date) as total
        FROM {$logTable}
        {$whereStr}
        ";

		$data = $dbc->fetch_all_assoc($sql,array_merge(
			$whereValues,
			array(
				$start,
				$pageEnd
			)
		));

		$totalResults = $dbc->fetch_all_assoc($totalSql,$whereValues);
		$total = $totalResults[0]['total'];

		$outputData = array();
		foreach($data as $row){
			$row = FormatModel::formatQueryOutput($row,$logTableColumns);
			$outputData[] = array_values($row);
		}

		return array(
			'totalRows' => $total,
			'limit' => $limit,
			'data' =>  $outputData
		);
	}
}