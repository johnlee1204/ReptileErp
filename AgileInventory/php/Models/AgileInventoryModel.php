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
			['locationId' => $locationId],
			'ORDER BY binName'
		);

		return self::$database->fetch_all_row();
	}

	static function readOnHandForBin($binId) {
		return self::$database->fetch_all_row("
			SELECT
				OnHand.onHandId,
				Bin.binName,
				OnHand.productId,
				Product.productName,
				Product.productDescription,
				OnHand.quantity
			FROM OnHand
			LEFT JOIN Bin ON Bin.binId = OnHand.binId
			LEFT JOIN Product ON Product.productId = OnHand.productId
			WHERE
				OnHand.binId = ?
		", [$binId]);
	}

	static function readOnHandForLocation($locationId) {
		return self::$database->fetch_all_row("
			SELECT
				OnHand.onHandId,
				Bin.binName,
				OnHand.productId,
				Product.productName,
				Product.productDescription,
				OnHand.quantity
			FROM OnHand
			LEFT JOIN Bin ON Bin.binId = OnHand.binId
			LEFT JOIN Product ON Product.productId = OnHand.productId
			WHERE
				Bin.locationId = ?
		", [$locationId]);
	}

	static function adjustQuantity($inputs) {
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
				'quantity' => $inputs['adjustmentQuantity']
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
				'productId' => $inputs['productId']
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
					'quantity' => $inputs['adjustmentQuantity']
				]
			);
			$currentOnHandQuantity = 0;
		} else {
			self::$database->update(
				"OnHand",
				['quantity' => $onHandRecord['quantity'] + $inputs['adjustmentQuantity']],
				['onHandId' => $onHandRecord['onHandId']]
			);
			$currentOnHandQuantity = $onHandRecord['quantity'];
		}

		if($currentOnHandQuantity + $inputs['adjustmentQuantity'] < 0) {
			throw new AgileUserMessageException("Cannot Adjust Quantity to Negative!");
		}

		self::$database->delete(
			"OnHand",
			['quantity' => 0]
		);
	}

	static function transferQuantity($inputs) {
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
				'quantity' => $inputs['transferQuantity']
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
				'productId' => $inputs['productId']
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
					'quantity' => $inputs['transferQuantity']
				]
			);
		} else {
			self::$database->update(
				"OnHand",
				['quantity' => $toOnHandRecord['quantity'] + $inputs['transferQuantity']],
				['onHandId' => $toOnHandRecord['onHandId']]
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
				'productId' => $inputs['productId']
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
					'quantity' => $inputs['transferQuantity']
				]
			);
			$fromOnHandQuantity = 0;
		} else {
			self::$database->update(
				"OnHand",
				['quantity' => $fromOnHandRecord['quantity'] + $inputs['transferQuantity']],
				['onHandId' => $fromOnHandRecord['onHandId']]
			);
			$fromOnHandQuantity = $fromOnHandRecord['quantity'];
		}

		if($fromOnHandQuantity + $inputs['transferQuantity'] < 0) {
			throw new AgileUserMessageException("Cannot Adjust More than in From Loc/Bin!");
		}

		self::$database->delete(
			"OnHand",
			['quantity' => 0]
		);
	}
}