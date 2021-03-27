<?php


use Job\Models\JobModel;

class Job extends AgileBaseController
{

	function searchJobs() {
		$inputs = Validation::validateJsonInput([
			'jobNumber',
			'part'
		]);
		$jobs = JobModel::searchJobs($inputs);
		$output = [];

		foreach($jobs as $job) {
			$job['quantity'] = SmartTruncate::truncate($job['quantity']);
			$output[] = array_values($job);
		}

		$this->outputSuccessData($output);
	}
	function readJob() {
		$input = Validation::validateJsonInput([
			'jobId' => 'numeric'
		]);

		$this->outputSuccessData(JobModel::readJob($input['jobId']));
	}

	function createJob() {
		$inputs = Validation::validateJsonInput([
			'part' => 'numeric',
			'quantity' => 'numeric',
			'jobStartDate' => 'notBlank'
		]);

		$this->database->begin_transaction();
		$newJobId = JobModel::createJob($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($newJobId);
	}

	function updateJob() {
		$inputs = Validation::validateJsonInput([
			'jobId',
			'part' => 'numeric',
			'quantity' => 'numeric',
			'jobStartDate' => 'notBlank',
			'status' => 'notBlank'
		]);

		$this->database->begin_transaction();
		JobModel::updateJob($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteJob() {
		$input = Validation::validateJsonInput([
			'jobId'
		]);

		$this->database->begin_transaction();
		JobModel::deleteJob($input['jobId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readJobBom() {
		$input = Validation::validateJsonInput([
			'jobId' => 'numeric'
		]);

		$this->outputSuccessData(JobModel::readJobBom($input['jobId']));
	}

	function readJobRoutings() {
		$input = Validation::validateJsonInput([
			'jobId' => 'numeric'
		]);

		$routings = JobModel::readJobRoutings($input['jobId']);
		$output = [];
		foreach($routings as $routing) {
			$routing['totalQuantity'] = SmartTruncate::truncate($routing['totalQuantity']);
			$routing['quantityComplete'] = SmartTruncate::truncate($routing['quantityComplete']);
			$output[] = array_values($routing);
		}

		$this->outputSuccessData($output);
	}

	function readParentJob() {
		$input = Validation::validateJsonInput([
			'jobId' => 'numeric'
		]);

		$this->outputSuccessData(JobModel::readParentJob($input['jobId']));
	}

	function readFirstJob() {
		$this->outputSuccessData(JobModel::readFirstJob());
	}

	function readPreviousJob() {
		$input = Validation::validateJsonInput([
			'jobId' => 'numeric'
		]);

		$this->outputSuccessData(JobModel::readPreviousJob($input['jobId']));
	}

	function readNextJob() {
		$input = Validation::validateJsonInput([
			'jobId' => 'numeric'
		]);

		$this->outputSuccessData(JobModel::readNextJob($input['jobId']));
	}

	function readLastJob() {
		$this->outputSuccessData(JobModel::readLastJob());
	}
}