<?php


use AgileInventory\Models\AgileInventoryModel;
use AgileInventory\Models\BinModel;
use AgileInventory\Models\FacilityModel;
use AgileInventory\Models\LocationModel;
use AgileInventory\Models\ProductModel;
use Libraries\Curl;

class AgileInventory extends AgileBaseController {
	private $apiKey;
	function init() {
		$this->database->selectDatabase("AgileInventory");
		$this->apiKey = "22c5eb452caddfcd8a9a0ffaed31a38d";
	}

	function readAppInitData() {
		$this->outputSuccessData(AgileInventoryModel::readShopInfo());
	}

	function readLocationInitData() {
		$this->outputSuccess([
			'facilities' => FacilityModel::readFacilitiesCombo()
		]);
	}

	function readFacilities() {
		$this->outputSuccessData(FacilityModel::readFacilities());
	}

	function readFacility() {
		$input = Validation::validateJsonInput([
			'facilityId' => 'numeric'
		]);

		$this->outputSuccessData(FacilityModel::readFacility($input['facilityId']));
	}

	function createFacility() {
		$inputs = Validation::validateJsonInput([
			'facilityName' => 'notBlank',
			'facilityDescription',
			'address1',
			'address2',
			'city',
			'province',
			'zip',
			'country',
			'phone',
		]);

		$this->database->begin_transaction();
		$facilityId = FacilityModel::createFacility($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($facilityId);
	}

	function updateFacility() {
		$inputs = Validation::validateJsonInput([
			'facilityId' => 'numeric',
			'facilityName' => 'notBlank',
			'facilityDescription',
			'address1',
			'address2',
			'city',
			'province',
			'zip',
			'country',
			'phone',
		]);

		$this->database->begin_transaction();
		FacilityModel::updateFacility($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteFacility() {
		$input = Validation::validateJsonInput([
			'facilityId' => 'numeric'
		]);

		$this->database->begin_transaction();
		FacilityModel::deleteFacility($input['facilityId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
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
			'locationDescription',
			'facility' => 'numericOrNull'
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
			'locationDescription',
			'facility' => 'numericOrNull'
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

	function readProductsCombo() {
		$this->outputSuccessData(ProductModel::readProductsCombo());
	}

	function readProduct() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readProduct($input['productId']));
	}

	function generateSku() {
		$this->outputSuccessData(AgileInventoryModel::generateSku());
	}

	function createProduct() {
		$inputs = Validation::validateJsonInput([
			'productName' => 'notBlank',
			'productDescription',
			'sku',
			'onWebsite' => 'checkBox',
			'primaryLocation' => 'numericOrNull',
			'primaryBin' => 'numericOrNull',
			'secondaryLocation' => 'numericOrNull',
			'secondaryBin' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		$productId = ProductModel::createProduct($inputs);
		if($inputs['onWebsite'] === 1) {
			$this->shopifyCreateProduct($inputs, $productId);
		}
		$this->database->commit_transaction();

		$this->outputSuccessData($productId);
	}

	function updateProduct() {
		$inputs = Validation::validateJsonInput([
			'productId' => 'numeric',
			'productName' => 'notBlank',
			'productDescription',
			'sku',
			'onWebsite' => 'checkBox',
			'primaryLocation' => 'numericOrNull',
			'primaryBin' => 'numericOrNull',
			'secondaryLocation' => 'numericOrNull',
			'secondaryBin' => 'numericOrNull'
		]);

		$product = ProductModel::readProduct($inputs['productId']);
		$this->database->begin_transaction();

		ProductModel::updateProduct($inputs);

		if($product['onWebsite'] === 0 && $inputs['onWebsite'] === 1) {
			$this->shopifyCreateProduct($inputs, $inputs['productId']);
		} else if($product['onWebsite'] === 1 && $inputs['onWebsite'] === 0) {
			$this->shopifyDeleteProduct($product['shopifyProductId']);
		} else if($product['onWebsite'] === 1 && $inputs['onWebsite'] === 1) {
			$this->shopifyUpdateProduct($inputs);
		}

		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deleteProduct() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->database->begin_transaction();
		$product = ProductModel::readProduct($input['productId']);
		ProductModel::deleteProduct($input['productId']);

		if($product['onWebsite'] === 1) {
			$this->shopifyDeleteProduct($product['shopifyProductId']);
		}

		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function readComponents() {
		$input = Validation::validateJsonInput([
			'productId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readComponents($input['productId']));
	}

	function readComponent() {
		$input = Validation::validateJsonInput([
			'componentId' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::readComponent($input['componentId']));
	}

	function createComponent() {
		$inputs = Validation::validateJsonInput([
			'parentProductId' => 'numeric',
			'productId' => 'numeric',
			'quantity' => 'numeric'
		]);

		$this->outputSuccessData(ProductModel::createComponent($inputs));
	}

	function updateComponent() {
		$inputs = Validation::validateJsonInput([
			'componentId' => 'numeric',
			'productId' => 'numeric',
			'quantity' => 'numeric'
		]);

		ProductModel::updateComponent($inputs);

		$this->outputSuccess();
	}

	function deleteComponent() {
		$input = Validation::validateJsonInput([
			'componentId' => 'numeric'
		]);

		ProductModel::deleteComponent($input['componentId']);

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
		$accessToken = AgileInventoryModel::readAccessToken();
		echo "<pre>";print_r(Curl::get("https://" . $this->apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks.json"));
	}

	function readShop() {
		$store = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::get("https://" . $this->apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/shop.json");
		$reply = $reply['shop'];
		$this->database->insert(
			'Shop',
			[
				'shop' => $reply['domain'],
				'shopName' => $reply['name'],
				'shopifyId' => $reply['id'],
				'email' => $reply['email']
			]
		);
	}

	function addWebHooks() {
		$store = AgileInventoryModel::readShopFromCookie();

		$accessToken = AgileInventoryModel::readAccessToken();
		Curl::postJson("https://" . $this->apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks.json", [
			'webhook' => [
				'topic' => "products/create",
				'address' => "https://leesheet.com/AgileInventory/webHookProductAdded",
				'format' => "json"
			]
		]);

		Curl::postJson("https://" . $this->apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks.json", [
			'webhook' => [
				'topic' => "products/update",
				'address' => "https://leesheet.com/AgileInventory/webHookProductUpdated",
				'format' => "json"
			]
		]);

		Curl::postJson("https://" . $this->apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks.json", [
			'webhook' => [
				'topic' => "products/delete",
				'address' => "https://leesheet.com/AgileInventory/webHookProductDeleted",
				'format' => "json"
			]
		]);
	}

	function deleteWebHook() {
		$store = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		var_dump(Curl::sendRequest("DELETE", "https://" . $this->apiKey . ':' . $accessToken . '@' . $store . "/admin/api/2021-01/webhooks/1013564670138.json"));
	}

	function testWebHook() {
		var_dump(Curl::post("https://leesheet.com/AgileInventory/webHookProductAdded"));
	}

	function webHookProductAdded() {
		$productInfoJson = $loggedJson = file_get_contents('php://input');
		$productInfo = json_decode($productInfoJson, TRUE);
		$headers = apache_request_headers();
		if(!$this->verify_webhook($productInfoJson, $headers['X-Shopify-Hmac-Sha256'])) {
			exit;
		}

		$this->database->select(
			"Product",
			['productId'],
			['productName' => $productInfo['title']]
		);

		$product = $this->database->fetch_assoc();

		if($product !== NULL) {
			return;
		}

		$this->database->insert(
			"Product",
			[
				'productName' => $productInfo['title'],
				'productDescription' => '',
				'shopifyProductId' => $productInfo['id'],
				'shop' => $headers['X-Shopify-Shop-Domain'],
				'sku' => $productInfo['variants'][0]['sku'],
				'onWebsite' => 1
			]
		);
	}

	function webHookProductUpdated() {
		$productInfoJson = $loggedJson = file_get_contents('php://input');
		$productInfo = json_decode($productInfoJson, TRUE);
		$headers = apache_request_headers();
		if(!$this->verify_webhook($productInfoJson, $headers['X-Shopify-Hmac-Sha256'])) {
			exit;
		}
		$this->database->update(
			"Product",
			[
				'productName' => $productInfo['title'],
				'sku' => $productInfo['variants'][0]['sku']
			],
			[
				'shopifyProductId' => $productInfo['id'],
				'shop' => $headers['X-Shopify-Shop-Domain']
			]
		);
	}

	function webHookProductDeleted() {
		$productInfoJson = $loggedJson = file_get_contents('php://input');
		$productInfo = json_decode($productInfoJson, TRUE);
		$headers = apache_request_headers();
		if(!$this->verify_webhook($productInfoJson, $headers['X-Shopify-Hmac-Sha256'])) {
			exit;
		}
		$this->database->delete(
			"Product",
			[
				'shopifyProductId' => $productInfo['id'],
				'shop' => $headers['X-Shopify-Shop-Domain']
			]
		);
	}

	function verify_webhook($data, $hmac_header)
	{
		$secretKey = "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";
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data, $secretKey, true));
		return hash_equals($hmac_header, $calculated_hmac);
	}

	function appInstalled() {
		$shop = $_GET['shop'];
		$redirectUri = 'https://leesheet.com/AgileInventory/permissionsGranted';
		$nonce = $this->getNonce($_GET['hmac'], $shop);
		header('Location: https://' . $shop . '/admin/oauth/authorize?client_id=' . $this->apiKey . '&scope=write_products,read_locations&redirect_uri=' . $redirectUri . '&state=' . $nonce . '&grant_options[]={per-user}');
		exit;
	}

	function permissionsGranted() {
		//https://example.org/some/redirect/uri?code={authorization_code}&hmac=da9d83c171400a41f8db91a950508985&timestamp=1409617544&state={nonce}&shop={hostname}
		$shop = $_GET['shop'];
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
			'client_id' => $this->apiKey,
			'client_secret' => $secretKey,
			'code' => $authCode
		]);

		$accessToken = $reply['access_token'];

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
			$_COOKIE['AgileInventory'] = $cookie;
			$this->database->insert(
				"Session",
				[
					'sessionId' => $cookie,
					'shop' => $shop
				]
			);
		}

		$this->database->select(
			'AccessToken',
			['shop'],
			[
				'shop' => $shop,
				'accessToken' => $accessToken
			]
		);

		$accessTokenRecord = $this->database->fetch_assoc();

		if($accessTokenRecord === NULL) { //permissions first granted
			$this->database->delete(
				'AccessToken',
				[
					'shop' => $shop
				]
			);

			$this->database->insert(
				'AccessToken',
				[
					'shop' => $shop,
					'accessToken' => $accessToken
				]
			);
			$this->importProducts(FALSE);
			$this->importLocations();
			$this->readShop();
			$this->addWebHooks();
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

	public function importProducts($outputSuccess = TRUE) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::get("https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/products.json");
		if(!(isset($reply['products']) && is_array($reply['products']))) {
			return;
		}

		$productsImported = 0;

		foreach($reply['products'] as $product) {

			$this->database->select(
				"Product",
				['productId'],
				['shopifyProductId' => $product['id']]
			);

			$productRecord = $this->database->fetch_assoc();

			if($productRecord !== NULL) {
				continue;
			}

			$this->database->insert(
				"Product",
				[
					'productName' => $product['title'],
					'productDescription' => '',
					'sku' => $product['variants'][0]['sku'],
					'shopifyProductId' => $product['id'],
					'onWebsite' => 1,
					'shop' => $shop
				]
			);
			$productsImported++;
		}

		if($outputSuccess) {
			$this->outputSuccessData($productsImported);
		}
	}

	public function shopifyCreateProduct($inputs, $productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::postJson("https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/products.json", [
			'product' => [
				'title' => $inputs['productName'],
				'body_html' => '',
				'vendor' => '',
				'product_type' => '',
				'tags' => [],
				'variants' => [
					['sku' => $inputs['sku']]
				]
			]
		]);

		if(isset($reply['errors'])) {
			throw new Exception(var_export($reply));
		}

		$this->database->update(
			"Product",
			['shopifyProductId' => $reply['product']['id']],
			[
				'productId' => $productId,
				'shop' => $shop
			]
		);
	}

	public function shopifyUpdateProduct($inputs) {
		$product = ProductModel::readProduct($inputs['productId']);
		if($product['shopifyProductId'] === NULL) {
			return;
		}

		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::sendJsonRequest("PUT","https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/products/" . $product['shopifyProductId'] . ".json", [
			'product' => [
				'title' => $inputs['productName'],
				'id' => $product['shopifyProductId'],
				'variants' => [
					['sku' => $inputs['sku']]
				]
			]
		]);

		if(isset($reply['errors'])) {
			throw new Exception(var_export($reply));
		}
	}

	public function shopifyDeleteProduct($shopifyProductId) {
		if($shopifyProductId === NULL) {
			return;
		}

		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::sendRequest("DELETE","https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/products/" . $shopifyProductId . ".json");

		$this->database->update(
			"Product",
			['shopifyProductId' => NULL],
			['shopifyProductId' => $shopifyProductId]
		);

		if(isset($reply['errors'])) {
			throw new Exception(var_export($reply));
		}
	}

	public function importLocations() {
		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::get("https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/locations.json");
		if(!(isset($reply['locations']) && is_array($reply['locations']))) {
			return;
		}

		foreach($reply['locations'] as $location) {

			$this->database->select(
				"Facility",
				['facilityId'],
				['shopifyId' => $location['id']]
			);

			$locationRecord = $this->database->fetch_assoc();

			if($locationRecord !== NULL) {
				continue;
			}

			$this->database->insert(
				"Facility",
				[
					'shopifyId' => $location['id'],
					'facilityName' => $location['name'],
					'facilityDescription' => '',
					'shop' => $shop,
					'address1' => $location['address1'],
					'address2' => $location['address2'],
					'city' => $location['city'],
					'zip' => $location['zip'],
					'province' => $location['province'],
					'country' => $location['country'],
					'phone' => $location['phone']
				]
			);
		}
	}

	public function readProductsFromApi() {
		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::get("https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/products.json");
		echo "<pre>";print_r($reply);die();
	}

	public function readLocationsFromApi() {
		$shop = AgileInventoryModel::readShopFromCookie();
		$accessToken = AgileInventoryModel::readAccessToken();
		$reply = Curl::get("https://" . $this->apiKey . ':' . $accessToken . '@'. $shop . "/admin/api/2021-01/locations.json");
		echo "<pre>";print_r($reply);die();
	}

	function deleteEverything() {
		$input = Validation::validateGet([
			'shop' => ['default' => NULL]
		]);

		$shopFilter = [];
		if($input['shop'] !== NULL) {
			$shopFilter = ['shop' => $input['shop']];
		}

		$this->database->delete(
			"AccessToken",
			$shopFilter
		);

		$this->database->delete(
			"Bin",
			$shopFilter
		);

		$this->database->delete(
			"Location",
			$shopFilter
		);

		$this->database->delete(
			"Facility",
			$shopFilter
		);

		$this->database->delete(
			"OnHand",
			$shopFilter
		);

		$this->database->delete(
			"Product",
			$shopFilter
		);

		$this->database->delete(
			"Session",
			$shopFilter
		);

		$this->database->delete(
			"Transaction",
			$shopFilter
		);

		$this->database->delete(
			"Shop",
			$shopFilter
		);
	}
}