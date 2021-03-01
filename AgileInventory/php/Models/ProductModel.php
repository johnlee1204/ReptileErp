<?php


namespace AgileInventory\Models;


use AgileModel;

class ProductModel extends AgileModel {

	static function readProducts() {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_all_row("
			SELECT
				Product.productId,
				Product.productName,
				Product.productDescription
			FROM Product
			WHERE
				Product.shop = ?
			ORDER BY Product.productName
		", [$shop]);
	}

	static function readProduct($productId) {
		$shop = AgileInventoryModel::readShopFromCookie();
		return self::$database->fetch_assoc("			
			SELECT
				Product.productId,
				Product.productName,
				Product.productDescription,
				
				Product.primaryLocationId primaryLocation,
				PrimaryLocation.locationName primaryLocationName,
				
				Product.primaryBinId primaryBin,
				PrimaryBin.binName primaryBinName,
				
				Product.secondaryLocationId secondaryLocation,
				SecondaryLocation.locationName secondaryLocationName,
				
				Product.secondaryBinId secondaryBin,
				SecondaryBin.binName secondaryBinName
			FROM Product
			LEFT JOIN Location PrimaryLocation ON PrimaryLocation.locationId = Product.primaryLocationId AND PrimaryLocation.shop = ?
			LEFT JOIN Bin PrimaryBin ON PrimaryBin.binId = Product.primaryBinId AND PrimaryBin.shop = ?
		
			LEFT JOIN Location SecondaryLocation ON SecondaryLocation.locationId = Product.primaryLocationId AND SecondaryLocation.shop = ?
			LEFT JOIN Bin SecondaryBin ON SecondaryBin.binId = Product.primaryBinId AND SecondaryBin.shop = ?
			
			WHERE
				Product.productId = ?
			AND
				Product.shop = ?
		", [$shop, $shop, $shop, $shop, $productId, $shop]);
	}

	static function createProduct($inputs) {
		$shop = AgileInventoryModel::readShopFromCookie();
		$productId = self::$database->insert(
			"Product",
			[
				'productName' => $inputs['productName'],
				'productDescription' => $inputs['productDescription'],
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
			LEFT JOIN Location ON Location.locationId = OnHand.locationId AND Location.shop = ?
			LEFT JOIN Bin ON Bin.binId = OnHand.binId AND Bin.shop = ?
			LEFT JOIN Product ProductPrimaryDesignation ON ProductPrimaryDesignation.productId = OnHand.productId AND ProductPrimaryDesignation.primaryBinId = OnHand.binId AND ProductPrimaryDesignation.shop = ?
			LEFT JOIN Product ProductSecondaryDesignation ON ProductSecondaryDesignation.productId = OnHand.productId AND ProductSecondaryDesignation.secondaryBinId = OnHand.binId AND ProductSecondaryDesignation.shop = ?
			WHERE
				OnHand.productId = ?
			AND
				OnHand.shop = ?
			ORDER BY quantity DESC
		", [$shop, $shop, $shop, $shop, $productId, $shop]);
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
			LEFT JOIN Bin FromBin ON FromBin.binId = Transaction.fromBinId AND FromBin.shop = ?
			LEFT JOIN Bin ToBin ON ToBin.binId = Transaction.toBinId AND ToBin.shop = ?
			LEFT JOIN Location FromLocation ON FromLocation.locationId = Transaction.fromLocationId AND FromLocation.shop = ?
			LEFT JOIN Location ToLocation ON ToLocation.locationId = Transaction.toLocationId AND ToLocation.shop = ?
			WHERE
				Transaction.productId = ?
			AND
				Transaction.shop = ?
			ORDER BY transactionDate DESC
		", [$shop, $shop, $shop, $shop, $productId, $shop]);
	}

}