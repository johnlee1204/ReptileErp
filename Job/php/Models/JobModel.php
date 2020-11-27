<?php

namespace Job\Models;
use AgileModel;
use AgileUserMessageException;
use ItemMaster\Models\ItemMasterModel;
use SmartTruncate;

class JobModel extends AgileModel
{
	static function readJob($jobId) {
		$job = self::$database->fetch_assoc("
			SELECT
				Job.jobNumber,
				Job.partId part,
				Part.partName,
				Job.quantity,
				Job.status,
				Job.jobStartDate,
				Job.jobCreateDate
			FROM Job
			JOIN Part on Part.partId = Job.partId
			WHERE
				jobId = ?
		", [$jobId]);

		if($job !== NULL) {
			$job['quantity'] = SmartTruncate::truncate($job['quantity']);
		}

		return $job;
	}

	static function createJob($inputs) {

		if(!isset($inputs['jobNumber'])) {
			$inputs['jobNumber'] = self::readNextJobNumber();
		}

		self::$database->query("
			INSERT INTO Job(partId, quantity, status, jobStartDate)
			VALUES(?, ?, 'Open', STR_TO_DATE(?,'%m/%d/%Y %H:%i:%s'))
		", [$inputs['part'], $inputs['quantity'], $inputs['jobStartDate']]);

		$insertedId = self::$database->getInserted();

		self::$database->query("
			UPDATE Job
			SET
				jobNumber = ?
			WHERE 
				jobId = ?
		", [$inputs['jobNumber'], $insertedId]);

		self::createSubJobs($inputs['jobNumber'], $inputs['part'], $inputs['quantity'], $inputs['jobStartDate']);

		return $insertedId;
	}

	private static function createSubJobs($jobNumber, $partId, $quantity, $jobStartDate) {
		$partChildren = ItemMasterModel::readPartChildren($partId);

		foreach($partChildren as $child) {
			$child['jobStartDate'] = $jobStartDate;
			$child['quantity'] = $child['quantity'] * $quantity;
			$child['jobNumber'] = self::readNextSubJobNumber($jobNumber);

			self::createJob($child);
		}
	}

	private static function readNextJobNumber() {
		$maxJobNumber = self::$database->fetch_assoc("
			SELECT MAX(jobNumber) jobNumber FROM Job
		");

		$maxJobNumber = $maxJobNumber['jobNumber'];

		if(strlen($maxJobNumber) !== 10) {
			return '00001-0000';
		}

		$maxJobNumber = substr($maxJobNumber, 0, 5);

		$maxJobNumber = intval($maxJobNumber);

		$maxJobNumber++;

		$maxJobNumber = str_pad($maxJobNumber, 5, "0", STR_PAD_LEFT);

		return $maxJobNumber . '-0000';
	}

	private static function readNextSubJobNumber($jobNumber) {
		if(strlen($jobNumber) !== 10 && strlen($jobNumber) !== 5) {
			throw new AgileUserMessageException("Invalid Job Number: " . $jobNumber);
		}

		$jobNumber = substr($jobNumber, 0, 5);

		$maxJobNumber = self::$database->fetch_assoc("
			SELECT MAX(jobNumber) jobNumber FROM Job
			WHERE jobNumber LIKE CONCAT(?,'%')
		", [$jobNumber]);

		$maxJobNumber = $maxJobNumber['jobNumber'];

		$maxJobNumber = substr($maxJobNumber, 6);

		$maxJobNumber = intval($maxJobNumber);

		$maxJobNumber++;

		$maxJobNumber = str_pad($maxJobNumber, 4, "0", STR_PAD_LEFT);

		return $jobNumber . '-' . $maxJobNumber;
	}
}