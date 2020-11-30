<?php

namespace Job\Models;
use AgileModel;
use AgileUserMessageException;
use ItemMaster\Models\ItemMasterModel;
use SmartTruncate;

class JobModel extends AgileModel
{

	static function searchJobs($inputs) {
		$where = [];
		$params = [];

		if($inputs['jobNumber']) {
			$where[] = "Job.jobNumber LIKE CONCAT(?,'%')";
			$params[] = $inputs['jobNumber'];
		}

		if($inputs['part']) {
			$where[] = "Job.partId = ?";
			$params[] = $inputs['part'];
		}

		$where = join(" AND ", $where);

		if($where !== "") {
			$where = "WHERE " . $where;
		}

		return self::$database->fetch_all_assoc("
			SELECT
				Job.jobId,
				Job.jobNumber,
				Part.partName,
				CAST(Job.jobStartDate AS DATE) jobStartDate,
				Job.quantity
			FROM Job
			JOIN Part ON Part.partId = Job.partId
			{$where}
			ORDER BY jobNumber DESC
		", $params);

	}

	static function readJob($jobId) {
		$job = self::$database->fetch_assoc("
			SELECT
				Job.jobNumber,
				Job.partId part,
				Part.partName,
				Job.quantity,
				Job.status,
				CAST(Job.jobStartDate AS DATE) jobStartDate,
				CAST(Job.jobCreateDate AS DATE) jobCreateDate
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
			VALUES(?, ?, 'Open', ?)
		", [$inputs['part'], $inputs['quantity'], $inputs['jobStartDate']]);

		$insertedId = self::$database->getInserted();

		self::$database->query("
			UPDATE Job
			SET
				jobNumber = ?
			WHERE 
				jobId = ?
		", [$inputs['jobNumber'], $insertedId]);

		self::createSubJobs($insertedId, $inputs['jobNumber'], $inputs['part'], $inputs['quantity'], $inputs['jobStartDate']);

		return $insertedId;
	}

	private static function createSubJobs($jobId, $jobNumber, $partId, $quantity, $jobStartDate) {
		$partChildren = ItemMasterModel::readPartChildren($partId);

		foreach($partChildren as $child) {
			$child['jobStartDate'] = $jobStartDate;
			$child['unitQuantity'] = $child['quantity'];
			$child['quantity'] = $child['quantity'] * $quantity;
			$child['jobNumber'] = self::readNextSubJobNumber($jobNumber);

			$child['jobId'] = $jobId;

			if($child['source'] === 'Make') {
				$subJobId = self::createJob($child);
				$child['subJobId'] = $subJobId;
			} else {
				$child['subJobId'] = NULL;
			}

			self::createJobBomRecord($child);
		}
	}

	static function createJobBomRecord($inputs) {
		self::$database->query("
			INSERT INTO JobBillOfMaterial(jobId, partId, quantity, totalQuantity, quantityIssued, subJobId)
			VALUES(?, ?, ?, ?, ?, ?)
		", [$inputs['jobId'], $inputs['part'], $inputs['unitQuantity'], $inputs['quantity'], 0, $inputs['subJobId']]);
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

	static function readJobBom($jobId) {
		$job = self::readJob($jobId);

		$jobBom = self::$database->fetch_assoc("
			SELECT
				Part.partName,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN 'TOP JOB' ELSE bomJob.jobNumber END jobNumber,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN '' ELSE bomJob.jobId END jobId,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN '' ELSE JobBillOfMaterial.jobBillOfMaterialId END jobBillOfMaterialId,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN {$job['quantity']} ELSE JobBillOfMaterial.quantity END quantity,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN {$job['quantity']} ELSE JobBillOfMaterial.totalQuantity END totalQuantity,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN Job.jobId ELSE JobBillOfMaterial.subJobId END subJobId,
				CASE WHEN JobBillOfMaterial.jobBillOfMaterialId IS NULL THEN Job.jobNumber ELSE childJob.jobNumber END subJob,
				Part.source
			FROM Job
			LEFT JOIN JobBillOfMaterial ON JobBillOfMaterial.subJobId = ? AND JobBillOfMaterial.partId = Job.partId
			LEFT JOIN Job bomJob ON bomJob.jobId = JobBillOfMaterial.jobId
			LEFT JOIN Job childJob ON childJob.jobId = JobBillOfMaterial.subJobId
			JOIN Part on Part.partId = Job.partId
			WHERE
				Job.jobId = ?
		", [$jobId, $jobId]);

		$jobBom['children'] = [];

		self::readJobBomRecursive($jobId, $jobBom['children']);

		return $jobBom;
	}

	static function readJobBomRecursive($jobId, &$jobBom) {
		$children = self::$database->fetch_all_assoc("
			SELECT
				Part.partName,
				Job.jobNumber,
				JobBillOfMaterial.jobBillOfMaterialId,
				JobBillOfMaterial.quantity,
				JobBillOfMaterial.totalQuantity,
				JobBillOfMaterial.jobId,
				JobBillOfMaterial.subJobId,
				childJob.jobNumber subJob,
				Part.source
			FROM JobBillOfMaterial
			JOIN Part ON Part.partId = JobBillOfMaterial.partId
			JOIN Job ON Job.jobId = JobBillOfMaterial.jobId
			LEFT JOIN Job childJob ON childJob.jobId = JobBillOfMaterial.subJobId
			WHERE
				JobBillOfMaterial.jobId = ?
		", [$jobId]);

		foreach($children as $child) {
			$child['quantity'] = SmartTruncate::truncate($child['quantity']);
			$child['totalQuantity'] = SmartTruncate::truncate($child['totalQuantity']);
			$jobBom[] = $child;
			$jobBom[count($jobBom) - 1]['children'] = [];
			self::readJobBomRecursive($child['subJobId'], $jobBom[count($jobBom) - 1]['children']);
		}
	}

	static function updateJob($inputs) {
		$oldJob = self::readJob($inputs['jobId']);
		if(!isset($inputs['quantityMultiplier'])) {
			$inputs['quantityMultiplier'] = $inputs['quantity'] / $oldJob['quantity'];
		}

		self::$database->query("
			UPDATE Job
			SET
				quantity = ?,
				jobStartDate = ?,
				status = ?
			WHERE
				jobId = ?
		", [$inputs['quantity'], $inputs['jobStartDate'], $inputs['status'], $inputs['jobId']]);

		self::updateSubJobs($inputs['jobId'], $inputs);
	}

	static function updateSubJobs($jobId, $inputs) {

		$subJobs = self::$database->fetch_all_assoc("
			SELECT
				JobBillOfMaterial.subJobId jobId,
				JobBillOfMaterial.partId part,
				JobBillOfMaterial.quantity,
				JobBillOfMaterial.totalQuantity,
				Job.jobStartDate,
				Job.status,
				Part.source
			FROM JobBillOfMaterial
			LEFT JOIN Job ON Job.jobId = JobBillOfMaterial.subJobId
			JOIN Part ON Part.partId = JobBillOfMaterial.partId
			WHERE
				JobBillOfMaterial.jobId = ?
		", [$jobId]);

		foreach($subJobs as $subJob) {
			$subJob['quantity'] = $subJob['totalQuantity'] * $inputs['quantityMultiplier'];

			self::updateJobBomRecordOnJobUpdate($inputs['quantityMultiplier'], $jobId, $subJob['part']);

			if($subJob['source'] === "Make") {
				self::updateJob($subJob);
			}
		}
	}

	static function updateJobBomRecordOnJobUpdate($jobId, $quantityMultiplier, $part) {
		self::$database->query("
			UPDATE JobBillOfMaterial
			SET
				totalQuantity = totalQuantity * ?
			WHERE
				jobId = ?
			AND
				partId = ?
		", [$quantityMultiplier, $jobId, $part]);
	}

	static function deleteJob($jobId) {
		self::$database->query("
			DELETE FROM Job
			WHERE
				jobId = ?
		", [$jobId]);

		self::deleteSubJobs($jobId);
	}

	static function deleteSubJobs($jobId) {
		$subJobs = self::$database->fetch_all_assoc("
			SELECT
				JobBillOfMaterial.subJobId jobId,
				JobBillOfMaterial.partId part,
				Part.source
			FROM JobBillOfMaterial
			JOIN Part ON Part.partId = JobBillOfMaterial.partId
			WHERE
				JobBillOfMaterial.jobId = ?
		", [$jobId]);

		foreach($subJobs as $subJob) {

			self::deleteJobBomRecordOnJobDelete($jobId, $subJob['part']);

			if($subJob['source'] === "Make") {
				self::deleteJob($subJob['jobId']);
			}
		}
	}

	static function deleteJobBomRecordOnJobDelete($jobId, $part) {
		self::$database->query("
			DELETE FROM JobBillOfMaterial
			WHERE
				jobId = ?
			AND
				partId = ?
		", [$jobId, $part]);
	}
}