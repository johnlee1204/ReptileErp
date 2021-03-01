<?php


use AgileInventory\Models\AgileInventoryModel;
use AgileInventory\Models\BinModel;
use AgileInventory\Models\LocationModel;
use AgileInventory\Models\ProductModel;
use Libraries\Curl;

class AgileInventory extends AgileBaseController {
	function init() {
		$this->database->selectDatabase("AgileInventory");
	}

	function readLocations() {
		$this->outputSuccessData(LocationModel::readLocations());
	}

	function readBinsForLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->outputSuccessData(AgileInventoryModel::readBinsForLocation($input['locationId']));
	}

	function readOnHandForBin() {
		$input = Validation::validateJsonInput([
			'binId' => 'numeric'
		]);

		$this->outputSuccessData(AgileInventoryModel::readOnHandForBin($input['binId']));
	}

	function readOnHandForLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->outputSuccessData(AgileInventoryModel::readOnHandForLocation($input['locationId']));
	}

	function readLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->outputSuccessData(LocationModel::readLocation($input['locationId']));
	}

	function createLocation() {
		$inputs = Validation::validateJsonInput([
			'locationName' => 'notBlank',
			'locationDescription'
		]);

		$this->database->begin_transaction();
		$locationId = LocationModel::createLocation($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($locationId);
	}

	function updateLocation() {
		$inputs = Validation::validateJsonInput([
			'locationId' => 'numeric',
			'locationName' => 'notBlank',
			'locationDescription'
		]);

		$this->database->begin_transaction();
		$locationId = LocationModel::updateLocation($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteLocation() {
		$input = Validation::validateJsonInput([
			'locationId' => 'numeric'
		]);

		$this->database->begin_transaction();
		LocationModel::deleteLocation($input['locationId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readBins() {
		$this->outputSuccessData(BinModel::readBins());
	}

	function readBin() {
		$input = Validation::validateJsonInput([
			'binId' => 'numeric'
		]);

		$this->outputSuccessData(BinModel::readBin($input['binId']));
	}

	function createBin() {
		$inputs = Validation::validateJsonInput([
			'binName' => 'notBlank',
			'binDescription',
			'location' => 'numeric'
		]);

		$this->database->begin_transaction();
		$binId = BinModel::createBin($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($binId);
	}

	function updateBin() {
		$inputs = Validation::validateJsonInput([
			'binId' => 'numeric',
			'binName' => 'notBlank',
			'binDescription',
			'location' => 'numeric'
		]);

		$this->database->begin_transaction();
		BinModel::updateBin($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteBin() {
		$input = Validation::validateJsonInput([
			'binId' => 'numeric'
		]);

		$this->database->begin_transaction();
		BinModel::deleteBin($input['binId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readProducts() {
		$this->outputSuccessData(ProductModel::readProducts());
	}

	function readProduct() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readProduct($input['productId']));
	}

	function createProduct() {
		$inputs = Validation::validateJsonInput([
			'productName' => 'notBlank',
			'productDescription',
			'primaryLocation' => 'numericOrNull',
			'primaryBin' => 'numericOrNull',
			'secondaryLocation' => 'numericOrNull',
			'secondaryBin' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		$productId = ProductModel::createProduct($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($productId);
	}

	function updateProduct() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'productName' => 'notBlank',
			'productDescription',
			'primaryLocation' => 'numericOrNull',
			'primaryBin' => 'numericOrNull',
			'secondaryLocation' => 'numericOrNull',
			'secondaryBin' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		ProductModel::updateProduct($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteProduct() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->database->begin_transaction();
		ProductModel::deleteProduct($input['productId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readOnHand() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readOnHand($input['productId']));
	}

	function readTransactionHistory() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readTransactionHistory($input['productId']));
	}

	function adjustQuantity() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'adjustmentType' => 'notBlank',
			'adjustmentQuantity' => ['tests' => ['numeric'], 'greaterThan' => 0],
			'adjustmentLocation' => 'numeric',
			'adjustmentBin' => 'numeric'
		]);

		$this->database->begin_transaction();
		AgileInventoryModel::adjustQuantity($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function transferQuantity() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'transferQuantity' => ['tests' => ['numeric'], 'greaterThan' => 0],
			'transferFromLocation' => 'numeric',
			'transferFromBin' => 'numeric',
			'transferToLocation' => 'numeric',
			'transferToBin' => 'numeric'
		]);

		$this->database->begin_transaction();
		AgileInventoryModel::transferQuantity($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readWebHooks() {
		$store = AgileInventoryModel::readShopFromCookie();
		$apiKey = "22c5eb452caddfcd8a9a0ffaed31a38d";
		$accessToken = AgileInventoryModel::readAccessToken();
		echo "<pre>";print_r(Curl::get("https://" . $apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks.json"));
	}

	function addWebHook() {
		$store = AgileInventoryModel::readShopFromCookie();
		$apiKey = "22c5eb452caddfcd8a9a0ffaed31a38d";
		$secretKey = "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";
		$accessToken = AgileInventoryModel::readAccessToken();
		var_dump(Curl::postJson("https://" . $apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks.json", [
			'webhook' => [
				'topic' => "products/create",
				'address' => "https://leesheet.com/AgileInventory/webHookProductAdded",
				'format' => "json"
			]
		]));
	}

	function deleteWebHook() {
		$store = AgileInventoryModel::readShopFromCookie();
		$apiKey = "22c5eb452caddfcd8a9a0ffaed31a38d";
		$secretKey = "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";
		$accessToken = AgileInventoryModel::readAccessToken();
		var_dump(Curl::sendRequest("DELETE", "https://" . $apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks/1013564670138.json"));
	}

	function webHookProductAdded() {
		$this->database->insert(
			'AccessToken',
			[
				'shop' => "It FUcking",
				'accessToken' => "Worked"
			]
		);
	}

	function appInstalled() {
		$shop = $_GET['shop'];
		$apiKey = "22c5eb452caddfcd8a9a0ffaed31a38d";
		$redirectUri = 'https://leesheet.com/AgileInventory/permissionsGranted';
		$nonce = $this->getNonce($_GET['hmac'], $shop);
		header('Location: https://' . $shop . '/admin/oauth/authorize?client_id=' . $apiKey . '&scope=read_products&redirect_uri=' . $redirectUri . '&state=' . $nonce . '&grant_options[]={per-user}');
		exit;
	}

	function permissionsGranted() {
		//https://example.org/some/redirect/uri?code={authorization_code}&hmac=da9d83c171400a41f8db91a950508985&timestamp=1409617544&state={nonce}&shop={hostname}
		$shop = $_GET['shop'];
		$apiKey = "22c5eb452caddfcd8a9a0ffaed31a38d";
		$secretKey = "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";
		$authCode = $_GET['code'];
		$hmac = $_GET['hmac'];
		$nonce = $_GET['state'];

		if(!$this->verifyNonce($shop, $nonce)) {
			echo "Nonce not verified";
			die();
		}

		if(preg_match("/\A(https|http)\:\/\/[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com\//", $shop) === 0 && preg_match("/\A[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com\z/", $shop) === 0) {
			echo "Invalid store name";
			die();
		}

		$withoutHmac = [];
		foreach($_GET as $key => $value) {
			if($key !== "hmac") {
				$withoutHmac[$key] = $key . '=' . $value;
			}
		}

		$urlString = join("&", $withoutHmac);

		$generatedHMac = hash_hmac("sha256", $urlString, $secretKey);

		if($generatedHMac != $_GET['hmac']) {
			echo "Invalid HMAC";
			die();
		}


		$reply = Curl::post("https://" . $shop . "/admin/oauth/access_token", [
			'client_id' => $apiKey,
			'client_secret' => $secretKey,
			'code' => $authCode
		]);

		$accessToken = $reply['access_token'];

		$this->database->delete(
			'AccessToken',
			[
				'shop' => $shop,
				'accessToken' => $accessToken
			]
		);

		$this->database->insert(
			'AccessToken',
			[
				'shop' => $shop,
				'accessToken' => $accessToken
			]
		);

		$session = NULL;

		if(isset($_COOKIE['AgileInventory'])) {
			$this->database->select(
				"Session",
				['sessionId'],
				[
					'sessionId' => $_COOKIE['AgileInventory'],
					'shop' => $shop
				]
			);

			$session = $this->database->fetch_assoc();
		}

		if($session === NULL) {
			$cookie = $hash = hash("sha256",openssl_random_pseudo_bytes(128));
			setcookie("AgileInventory", $cookie, time() + 60*60*24*365, "/");
			$this->database->insert(
				"Session",
				[
					'sessionId' => $cookie,
					'shop' => $shop
				]
			);
		}

		header('Location: https://leesheet.com/AgileInventory');
		exit;
	}

	private function getNonce($hmac, $shop) {
		$id = $hmac;
		$nonce = hash('sha512', $this->makeRandomString());
		$this->database->insert(
			"SetupNonce",
			[
				'id' => $shop,
				'nonce' => $nonce
			]
		);

		return $nonce;
	}

	private function verifyNonce($shop, $nonce) {
		$this->database->select(
			"SetupNonce",
			['id'],
			[
				'id' => $shop,
				'nonce' => $nonce
			]
		);

		$nonceRecord = $this->database->fetch_assoc();

		$this->database->delete(
			"SetupNonce",
			[
				'id' => $shop,
				'nonce' => $nonce
			]
		);

		return $nonceRecord !== NULL;
	}

	private function makeRandomString($bits = 256) {
		$bytes = ceil($bits / 8);
		$return = '';
		for ($i = 0; $i < $bytes; $i++) {
			$return .= chr(mt_rand(0, 255));
		}
		return $return;
	}
}