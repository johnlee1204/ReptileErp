<?php


class AgileBaseController {
	public $database;
	public $AgileApp;

	use AgileOutput;

	protected function loadModel($model){
		return $this->AgileApp->loadModel($model);
	}
}