<?php


class AgileBaseController {

	/* @var mysql_helper */
	public $database;
	public $AgileApp;

	use AgileOutput;

	protected function loadModel($model){
		return $this->AgileApp->loadModel($model);
	}
}