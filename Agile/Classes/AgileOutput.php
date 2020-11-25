<?php

trait AgileOutput
{
	public $AgileOutput;
	public $AgileOutputJson;

	protected function outputSuccess($data = false)
	{
		if ($data === false) {
			$this->outputJson(array('success' => true));
		} else {
			$this->outputJson(array_merge(array('success' => true), $data));
		}
	}

	/**
	 * @deprecated renamed to outputSuccessData()
	 */
	protected function outputSuccessDataJson($data)
	{
		$this->outputJson(array('success' => true, 'data' => $data));
	}

	protected function outputSuccessData($data)
	{
		$this->outputJson(array('success' => true, 'data' => $data));
	}

	/**
	 * @deprecated renamed to outputError()
	 */
	protected function outputErrorJson($error)
	{
		$this->outputJson(array('success' => false, 'error' => $error));
	}

	protected function outputError($error)
	{
		$this->outputJson(array('success' => false, 'error' => $error));
	}
	protected function outputJson($output)
	{
		if(FALSE === headers_sent() ){
			header('Content-Type: application/json; charset=utf-8');
		}

		$this->AgileOutput = $output;
		$this->AgileOutputJson = self::jsonEncode($output);
		echo $this->AgileOutputJson;
		//other output method which is not faster
		//file_put_contents('php://output', $this->AgileOutputJson);
	}
	protected static function jsonEncode($output){
		return AgileJson::encode($output);
	}

}