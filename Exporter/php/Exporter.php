<?php


use Libraries\Excel;

class Exporter extends AgileBaseController {
	function exportGrid() {
		$inputs = Validation::validateJsonInput([
			'fields',
			'exportData',
			'dataIndexType',
			'fileName'
		]);

//		$inputs['content'] = json_decode($inputs['content'], TRUE);
//		$inputs['fields'] = $inputs['content']['fields'];
//		$inputs['exportData'] = $inputs['content']['exportData'];
//		$inputs['dataIndexType'] = $inputs['content']['dataIndexType'];
//		$inputs['fileName'] = $inputs['content']['fileName'];

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

		Excel::outputExcelViaSpec($inputs['fields'], $inputs['exportData'], [], $inputs['fileName'] . ' ' . date("F j, Y g:i a"));
	}
}