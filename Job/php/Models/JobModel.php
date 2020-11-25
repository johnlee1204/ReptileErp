<?php

namespace Job\Models;
use AgileModel;
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
		self::$database->query("
			INSERT INTO Job(partId, quantity, status, jobStartDate)
			VALUES(?, ?, 'Open', STR_TO_DATE(?,'%m/%d/%Y %H:%i:%s'))
		", [$inputs['part'], $inputs['quantity'], $inputs['jobStartDate']]);

		//$insertedId = self::$database->getInserted();
		//return $insertedId;
	}


}