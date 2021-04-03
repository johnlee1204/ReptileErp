<?php


use Libraries\Excel;

class Exporter extends AgileBaseController {
	function exportGrid() {
		$inputs = Validation::validateGet([
			'fields' => 'notBlank',
			'exportData' => 'notBlank',
			'dataIndexType' => 'notBlank'
		]);

		$inputs['fields'] = json_decode($inputs['fields'], TRUE);
		$inputs['exportData'] = json_decode($inputs['exportData'], TRUE);
		$inputs['dataIndexType'] = json_decode($inputs['dataIndexType'], TRUE);

		foreach($inputs['exportData'] as &$record) {
			foreach($record as $key => &$value) {
				if(isset($inputs['dataIndexType'][$key]) && $inputs['dataIndexType'][$key] === 'datecolumn') {
					$date = strtotime($value);
					if($date !== FALSE) {
						$value = date("F j, Y g:i a", $date);
					}
				}
			}
		}

		Excel::outputExcelViaSpec($inputs['fields'], $inputs['exportData'], [], 'Grid Export ' . date("F j, Y g:i a"));
	}
}