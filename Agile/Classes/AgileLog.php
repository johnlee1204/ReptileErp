<?php

namespace Agile;

use Exception;
use sqlsrv_helper;

abstract class AgileLog extends AgileTable {

	/**
	 * Automatically determines where your log table should be
	 * and be named based on your agile config and class names.
	 *
	 * You can always override this function in your log table file
	 * if you need something really specific.
	 *
	 * @return array
	 */
	static function readTable(){
		$fullClass = get_called_class();
		$pieces = explode("\\",$fullClass);
		$className = $pieces[count($pieces)-1];
		$class = substr($className,0,strlen($className)-3);
		$logTable = 'log_'.$class;
		return [
			'database' => self::$agileApp->systemConfigs['database']['applicationLogDatabase'],
			'table' => $logTable
		];
	}

	static function readStandardApplicationLogColumns(){
		return [
			'date' => [
				Column::COLUMN => 'date',
				Column::TYPE => 'datetime',
				Column::LABEL => 'Date',
				Column::GRID_COLUMN_WIDTH => 140,
			],
			'userId' => [
				Column::COLUMN => 'userId',
				Column::TYPE => 'int',
				Column::LABEL => 'User Id',
				Column::GRID_COLUMN_WIDTH => 60,
			],
			'userName' => [
				Column::COLUMN => 'userName',
				Column::MAX_LENGTH => 101,
				Column::LABEL => 'User',
				Column::GRID_COLUMN_WIDTH => 100,
			],
			'action' => [
				Column::COLUMN => 'action',
				Column::MAX_LENGTH => 50,
				Column::LABEL => 'Action',
				Column::GRID_COLUMN_WIDTH => 150,
			],
			'data' => [
				Column::COLUMN => 'data',
				Column::LABEL => 'Data',
				Column::JSON => true,
				Column::GRID_COLUMN_WIDTH => 100,
				Column::GRID_COLUMN_HIDDEN => true
				//Column::FORMAT_FUNCTION => function($value){return self::formatJsonOutput($value);}
			]
		];
	}

	/**
	 * Utility function for calculating delta's for a log.
	 *
	 * @param $before
	 * @param $after
	 * @return array
	 */
	static function calculateDelta($before, $after){
		$changes = [];
		foreach($before as $field => $value){
			$changes[$field] = [
				'before' => $value,
				'after' => '',
			];
		}
		foreach($after as $field => $value){
			if(isset($changes[$field])){
				$changes[$field]['after'] = $value;
			}else{
				$changes[$field] = [
					'before' => '',
					'after' => $value,
				];
			}
		}
		$finalChanges = [];
		foreach($changes as $field => $change){
			if($change['before'] != $change['after']){
				$finalChanges[$field] = $change;
			}
		}
		ksort($finalChanges);
		return $finalChanges;
	}

	/**
	 * @param sqlsrv_helper $dbc
	 * @param $data
	 * @throws Exception
	 */
	static function log($dbc, $data){
		$data['date'] = date("Y-m-d H:i:s");
		if(!isset($data['userName']) && !isset($data['userId']) &&  FALSE !== $sessionData = self::$agileApp->SessionManager->getUserDataFromSession()){
			$data['userId'] = $sessionData['employeeId'];
			$data['userName'] = trim($sessionData['firstName'].' '.$sessionData['lastName']);
		}
		self::insert($dbc,$data);
	}
}