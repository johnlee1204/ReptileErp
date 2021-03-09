<?php


namespace AgileInventory\Models;


use AgileModel;

class ProductModel extends AgileModel {

	static function readProducts() {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Product",
			[
				'productId',
				'productName',
				'productDescription'
			],
			['shop' => $shop],
			'ORDER BY productName'
		);

		return self::$database->fetch_all_row();
	}

	static function readProductsCombo() {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->select(
			"Product",
			[
				'productId',
				'productName'
			],
			['shop' => $shop],
			'ORDER BY productName'
		);

		return self::$database->fetch_all_row();
	}

	static function readProduct($productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_assoc("			
			SELECT
				Product.productId,
				Product.productName,
				Product.productDescription,
				Product.sku,
				
				Product.primaryLocationId primaryLocation,
				PrimaryLocation.locationName primaryLocationName,
				
				Product.primaryBinId primaryBin,
				PrimaryBin.binName primaryBinName,
				
				Product.secondaryLocationId secondaryLocation,
				SecondaryLocation.locationName secondaryLocationName,
				
				Product.secondaryBinId secondaryBin,
				SecondaryBin.binName secondaryBinName,
				Product.onWebsite,
				shopifyProductId
			FROM Product
			LEFT JOIN Location PrimaryLocation ON PrimaryLocation.locationId = Product.primaryLocationId AND PrimaryLocation.shop = Product.shop
			LEFT JOIN Bin PrimaryBin ON PrimaryBin.binId = Product.primaryBinId AND PrimaryBin.shop = Product.shop
		
			LEFT JOIN Location SecondaryLocation ON SecondaryLocation.locationId = Product.primaryLocationId AND SecondaryLocation.shop = Product.shop
			LEFT JOIN Bin SecondaryBin ON SecondaryBin.binId = Product.primaryBinId AND SecondaryBin.shop = Product.shop
			
			WHERE
				Product.productId = ?
			AND
				Product.shop = ?
		", [$productId, $shop]);
	}

	static function createProduct($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$productId = self::$database->insert(
			"Product",
			[
				'productName' => $inputs['productName'],
				'productDescription' => $inputs['productDescription'],
				'sku' => $inputs['sku'],
				'onWebsite' => $inputs['onWebsite'],
				'primaryLocationId' => $inputs['primaryLocation'],
				'primaryBinId' => $inputs['primaryBin'],
				'secondaryLocationId' => $inputs['secondaryLocation'],
				'secondaryBinId' => $inputs['secondaryBin'],
				'shop' => $shop
			]
		);

		return $productId['id'];
	}

	static function updateProduct($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();

		self::$database->update(
			"Product",
			[
				'productName' => $inputs['productName'],
				'productDescription' => $inputs['productDescription'],
				'sku' => $inputs['sku'],
				'onWebsite' => $inputs['onWebsite'],
				'primaryLocationId' => $inputs['primaryLocation'],
				'primaryBinId' => $inputs['primaryBin'],
				'secondaryLocationId' => $inputs['secondaryLocation'],
				'secondaryBinId' => $inputs['secondaryBin']
			],
			[
				'productId' => $inputs['productId'],
				'shop' => $shop
			]
		);
	}

	static function deleteProduct($productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->delete(
			"Product",
			[
				'productId' => $productId,
				'shop' => $shop
			]
		);
	}

	static function readComponents($productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				Component.componentId,
				Component.productId,
				Product.productName,
				Component.quantity
			FROM Component
			JOIN Product ON Product.productId = Component.productId AND Product.shop = Component.shop
			WHERE
				Component.parentProductId = ?			
			AND
				Component.shop = ?
			ORDER BY Product.productName
		", [$productId, $shop]);
	}

	static function readComponent($componentId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_assoc("
			SELECT
				Component.productId,
				Component.parentProductId,
				Product.productName,
				Product.productDescription,
				Component.quantity
			FROM Component
			JOIN Product ON Product.productId = Component.productId AND Product.shop = Component.shop
			WHERE
				Component.componentId = ?
			AND
				Component.shop = ?
		", [$componentId, $shop]);
	}

	static function createComponent($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$componentId = self::$database->insert(
			"Component",
			[
				'parentProductId' => $inputs['parentProductId'],
				'productId' => $inputs['productId'],
				'quantity' => $inputs['quantity'],
				'shop' => $shop
			]
		);

		return $componentId['id'];
	}

	static function updateComponent($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->update(
			"Component",
			[
				'productId' => $inputs['productId'],
				'quantity' => $inputs['quantity']
			],
			[
				'componentId' => $inputs['componentId'],
				'shop' => $shop
			]
		);
	}

	static function deleteComponent($componentId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		self::$database->delete(
			"Component",
			[
				'componentId' => $componentId,
				'shop' => $shop
			]
		);
	}

	static function readOnHand($productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				OnHand.onHandId,
				Location.locationId,
				Location.locationName,
				Bin.binId,
				Bin.binName,
				OnHand.quantity,
				CASE
					WHEN ProductPrimaryDesignation.productId IS NOT NULL AND ProductSecondaryDesignation.productId IS NOT NULL THEN 'Primary, Secondary'
					WHEN ProductPrimaryDesignation.productId IS NOT NULL THEN 'Primary'
					WHEN ProductSecondaryDesignation.productId IS NOT NULL THEN 'Secondary'
					ELSE ''
				END designation
			FROM OnHand
			LEFT JOIN Location ON Location.locationId = OnHand.locationId AND Location.shop = OnHand.shop
			LEFT JOIN Bin ON Bin.binId = OnHand.binId AND Bin.shop = OnHand.shop
			LEFT JOIN Product ProductPrimaryDesignation ON ProductPrimaryDesignation.productId = OnHand.productId AND ProductPrimaryDesignation.primaryBinId = OnHand.binId AND ProductPrimaryDesignation.shop = OnHand.shop
			LEFT JOIN Product ProductSecondaryDesignation ON ProductSecondaryDesignation.productId = OnHand.productId AND ProductSecondaryDesignation.secondaryBinId = OnHand.binId AND ProductSecondaryDesignation.shop = OnHand.shop
			WHERE
				OnHand.productId = ?
			AND
				OnHand.shop = ?
			ORDER BY quantity DESC
		", [$productId, $shop]);
	}

	static function readTransactionHistory($productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				Transaction.transactionId,
				Transaction.transactionDate,
				Transaction.quantity,
				FromLocation.locationName fromLocation,
				FromBin.binName fromBin,
				ToLocation.locationName toLocation,
				ToBin.binName toBin,
				Transaction.name,
				Transaction.comment,
				Transaction.type
			FROM Transaction
			LEFT JOIN Bin FromBin ON FromBin.binId = Transaction.fromBinId AND FromBin.shop = Transaction.shop
			LEFT JOIN Bin ToBin ON ToBin.binId = Transaction.toBinId AND ToBin.shop = Transaction.shop
			LEFT JOIN Location FromLocation ON FromLocation.locationId = Transaction.fromLocationId AND FromLocation.shop = Transaction.shop
			LEFT JOIN Location ToLocation ON ToLocation.locationId = Transaction.toLocationId AND ToLocation.shop = Transaction.shop
			WHERE
				Transaction.productId = ?
			AND
				Transaction.shop = ?
			ORDER BY transactionDate DESC
		", [$productId, $shop]);
	}

}