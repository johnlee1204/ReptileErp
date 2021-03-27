<?php

namespace PetMaster\Tables;

use Agile\AgileLog;
use Agile\Column;

class PetMasterLog extends AgileLog
{

	static function readTable()
	{
		return array(
			'table' => 'log_PetMaster',
			'database' => 'LeeSheet',
			'comparison' => true
		);
	}

	/**
	 * @inheritDoc
	 */
	static function readColumns()
	{
		return array_merge(self::readStandardApplicationLogColumns(), [
			'petId' => array(
				Column::LABEL => 'Id',
				Column::COLUMN => 'petId',
				Column::TYPE => 'int',
				Column::REQUIRED_CREATE => false,
				Column::USED_CREATE => false,
				Column::USED_UPDATE => TRUE,
				Column::REQUIRED_DELETE => true
			),
			'serial' => array(
				Column::LABEL => 'Serial',
				Column::COLUMN => 'serial',
				Column::TYPE => 'varchar',
				Column::REQUIRED_CREATE => false,
				Column::USED_CREATE => false,
				Column::USED_UPDATE => TRUE,
				Column::REQUIRED_DELETE => true
			),
			'changes' => [
				Column::COLUMN => 'changes',
				Column::LABEL => 'Changes',
				Column::JSON => true,
				Column::GRID_COLUMN_WIDTH => 100,
				Column::GRID_COLUMN_HIDDEN => true
			]
		]);
	}
}