<?php

namespace AgileInventory\Models;
use AgileModel;
use AgileUserMessageException;

class AgileInventoryModel extends AgileModel
{

	static function readBinsForLocation($locationId) {
		self::$database->select(
			"Bin",
			[
				'binId',
				'binName',
				'binDescription'
			],
			[
				'locationId' => $locationId,
				'shop' => self::readShopFromCookie()
			],
			'ORDER BY binName'
		);

		return self::$database->fetch_all_row();
	}

	static function readOnHandForBin($binId) {
		$shop = self::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				OnHand.onHandId,
				Bin.binName,
				OnHand.productId,
				Product.productName,
				Product.productDescription,
				OnHand.quantity
			FROM OnHand
			LEFT JOIN Bin ON Bin.binId = OnHand.binId AND Bin.shop = OnHand.shop
			LEFT JOIN Product ON Product.productId = OnHand.productId AND Product.shop = OnHand.shop
			WHERE
				OnHand.binId = ?
			AND
				OnHand.shop = ?
		", [$binId]);
	}

	static function readOnHandForLocation($locationId) {
		$shop = self::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				OnHand.onHandId,
				Bin.binName,
				OnHand.productId,
				Product.productName,
				Product.productDescription,
				OnHand.quantity
			FROM OnHand
			LEFT JOIN Bin ON Bin.binId = OnHand.binId AND Bin.shop = OnHand.shop
			LEFT JOIN Product ON Product.productId = OnHand.productId AND Product.shop = OnHand.shop
			WHERE
				Bin.locationId = ?
			AND
				OnHand.shop = ?
		", [$locationId, $shop]);
	}

	static function adjustQuantity($inputs) {
		$shop = self::readShopFromCookie();
		if(!isset($inputs['comment'])) {
			$inputs['comment'] = "";
		}

		if(!isset($inputs['name'])) {
			$inputs['name'] = "";
		}

		$fromLocationId = NULL;
		$fromBinId = NULL;
		$toLocationId = NULL;
		$toBinId = NULL;

		if($inputs['adjustmentType'] === "In") {
			$toLocationId = $inputs['adjustmentLocation'];
			$toBinId = $inputs['adjustmentBin'];
		} else {
			$fromLocationId = $inputs['adjustmentLocation'];
			$fromBinId = $inputs['adjustmentBin'];
			$inputs['adjustmentQuantity'] *= -1;
		}

		$currentDate = date("Y-m-d H:i:s");
		self::$database->insert(
			"Transaction",
			[
				'productId' => $inputs['productId'],
				'fromLocationId' => $fromLocationId,
				'fromBinId' => $fromBinId,
				'toLocationId' => $toLocationId,
				'toBinId' => $toBinId,
				'transactionDate' => $currentDate,
				'name' => $inputs['name'],
				'comment' => $inputs['comment'],
				'type' => 'Adjustment',
				'quantity' => $inputs['adjustmentQuantity'],
				'shop' => $shop
			]
		);

		self::$database->select(
			"OnHand",
			[
				'onHandId',
				'quantity'
			],
			[
				'locationId' => $inputs['adjustmentLocation'],
				'binId' => $inputs['adjustmentBin'],
				'productId' => $inputs['productId'],
				'shop' => $shop
			]
		);

		$onHandRecord = self::$database->fetch_assoc();

		if($onHandRecord == NULL) {
			self::$database->insert(
				"OnHand",
				[
					'locationId' => $inputs['adjustmentLocation'],
					'binId' => $inputs['adjustmentBin'],
					'productId' => $inputs['productId'],
					'quantity' => $inputs['adjustmentQuantity'],
					'shop' => $shop
				]
			);
			$currentOnHandQuantity = 0;
		} else {
			self::$database->update(
				"OnHand",
				['quantity' => $onHandRecord['quantity'] + $inputs['adjustmentQuantity']],
				[
					'onHandId' => $onHandRecord['onHandId'],
					'shop' => $shop
				]
			);
			$currentOnHandQuantity = $onHandRecord['quantity'];
		}

		if($currentOnHandQuantity + $inputs['adjustmentQuantity'] < 0) {
			throw new AgileUserMessageException("Cannot Adjust Quantity to Negative!");
		}

		self::$database->delete(
			"OnHand",
			[
				'quantity' => 0,
				'shop' => $shop
			]
		);
	}

	static function transferQuantity($inputs) {
		$shop = self::readShopFromCookie();
		if(!isset($inputs['comment'])) {
			$inputs['comment'] = "";
		}

		if(!isset($inputs['name'])) {
			$inputs['name'] = "";
		}

		if($inputs['transferFromBin'] === $inputs['transferToBin']) {
			throw new AgileUserMessageException("From Location/Bin = To Location/Bin!");
		}

		$currentDate = date("Y-m-d H:i:s");
		self::$database->insert(
			"Transaction",
			[
				'productId' => $inputs['productId'],
				'fromLocationId' => $inputs['transferFromLocation'],
				'fromBinId' => $inputs['transferFromBin'],
				'toLocationId' => $inputs['transferToLocation'],
				'toBinId' => $inputs['transferToBin'],
				'transactionDate' => $currentDate,
				'name' => $inputs['name'],
				'comment' => $inputs['comment'],
				'type' => 'Transfer',
				'quantity' => $inputs['transferQuantity'],
				'shop' => $shop
			]
		);

		self::$database->select(
			"OnHand",
			[
				'onHandId',
				'quantity'
			],
			[
				'locationId' => $inputs['transferToLocation'],
				'binId' => $inputs['transferToBin'],
				'productId' => $inputs['productId'],
				'shop' => $shop
			]
		);

		$toOnHandRecord = self::$database->fetch_assoc();

		if($toOnHandRecord == NULL) {
			self::$database->insert(
				"OnHand",
				[
					'locationId' => $inputs['transferToLocation'],
					'binId' => $inputs['transferToBin'],
					'productId' => $inputs['productId'],
					'quantity' => $inputs['transferQuantity'],
					'shop' => $shop
				]
			);
		} else {
			self::$database->update(
				"OnHand",
				['quantity' => $toOnHandRecord['quantity'] + $inputs['transferQuantity']],
				[
					'onHandId' => $toOnHandRecord['onHandId'],
					'shop' => $shop
				]
			);
		}

		$inputs['transferQuantity'] *= -1;

		self::$database->select(
			"OnHand",
			[
				'onHandId',
				'quantity'
			],
			[
				'locationId' => $inputs['transferFromLocation'],
				'binId' => $inputs['transferFromBin'],
				'productId' => $inputs['productId'],
				'shop' => $shop
			]
		);

		$fromOnHandRecord = self::$database->fetch_assoc();

		if($fromOnHandRecord == NULL) {
			self::$database->insert(
				"OnHand",
				[
					'locationId' => $inputs['transferFromLocation'],
					'binId' => $inputs['transferFromBin'],
					'productId' => $inputs['productId'],
					'quantity' => $inputs['transferQuantity'],
					'shop' => $shop
				]
			);
			$fromOnHandQuantity = 0;
		} else {
			self::$database->update(
				"OnHand",
				['quantity' => $fromOnHandRecord['quantity'] + $inputs['transferQuantity']],
				[
					'onHandId' => $fromOnHandRecord['onHandId'],
					'shop' => $shop
				]
			);
			$fromOnHandQuantity = $fromOnHandRecord['quantity'];
		}

		if($fromOnHandQuantity + $inputs['transferQuantity'] < 0) {
			throw new AgileUserMessageException("Cannot Adjust More than in From Loc/Bin!");
		}

		self::$database->delete(
			"OnHand",
			[
				'quantity' => 0,
				'shop' => $shop
			]
		);
	}

	static function readShopFromCookie() {
		$errorMessage = "Not Logged In! Open App through Shopify Admin!<BR><center><a href = 'https://shopify.com/'>Shopify</a></center>";
		if(!isset($_COOKIE['AgileInventory'])) {
			throw new AgileUserMessageException($errorMessage);
		}

		self::$database->select(
			"Session",
			['shop'],
			[
				'sessionId' => $_COOKIE['AgileInventory']
			]
		);

		$session = self::$database->fetch_assoc();

		if($session === NULL) {
			throw new AgileUserMessageException($errorMessage);
		}

		return $session['shop'];
	}

	static function readAccessToken() {
		$shop = self::readShopFromCookie();

		self::$database->select(
			"AccessToken",
			['accessToken'],
			[
				'shop' => $shop
			]
		);

		$accessToken = self::$database->fetch_assoc();
		if($accessToken === NULL) {
			throw new AgileUserMessageException("Could not find Access Token!");
		}

		return $accessToken['accessToken'];
	}

	static function readShopInfo() {
		$shop = self::readShopFromCookie();
		self::$database->select(
			'Shop',
			[
				'shopName'
			],
			['shop' => $shop]
		);

		$shopInfo = self::$database->fetch_assoc();

		if($shopInfo === NULL) {
			throw new AgileUserMessageException("Cannot find Shop!");
		}

		return $shopInfo['shopName'];
	}
}