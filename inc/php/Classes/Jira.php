<?php

class Jira {


	private $httpCode;

	private function apiCall($action, $params = NULL){
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => 'https://godevsgo.atlassian.net/rest/api/2/'.$action,
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json',
				'Content-Type: application/json'
			),
			CURLOPT_USERPWD => "jira48@fweco.net:q8okV88LrCZYxVA9umsb7827", //This is not a password, just an API key.
		));
		if($params !== NULL){
			curl_setopt_array($curl,array(
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode($params)
			));
		}
		$reply = curl_exec($curl);
		$this->httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		return $reply;
	}

	private function decodeJson($data){
		if(NULL === $json = json_decode($data,true)){
			throw new Exception('Failed to decode json!');
		}
		return $json;
	}

	function createTicket($params){
		$reply = $this->apiCall('issue',$params);
		if($this->httpCode !== 201){
			$e = new Exception('Failed to create Jira Ticket');
			$e->exceptionData = array('reply' => $reply,'httpCode' => $this->httpCode);
			throw $e;
		}
		return $this->decodeJson($reply);
	}

	function addWatcher($issue,$watcher){
		$this->apiCall('issue/'.$issue.'/watchers',$watcher);
		if($this->httpCode !== 204){
			throw new Exception('Failed to add watcher');
		}
	}

}