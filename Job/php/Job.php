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
}