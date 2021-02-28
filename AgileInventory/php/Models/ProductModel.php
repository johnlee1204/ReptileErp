<?php


namespace AgileInventory\Models;


use AgileModel;

class ProductModel extends AgileModel {

	static function readProducts() {
		return self::$database->fetch_all_row("
			SELECT
				Product.productId,
				Product.productName,
				Product.productDescription
			FROM Product
			ORDER BY Product.productName
		");
	}

	static function readProduct($productId) {
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
			LEFT JOIN Location PrimaryLocation ON PrimaryLocation.locationId = Product.primaryLocationId
			LEFT JOIN Bin PrimaryBin ON PrimaryBin.binId = Product.primaryBinId
		
			LEFT JOIN Location SecondaryLocation ON SecondaryLocation.locationId = Product.primaryLocationId
			LEFT JOIN Bin SecondaryBin ON SecondaryBin.binId = Product.primaryBinId
			
			WHERE
				Product.productId = ?
		", [$productId]);
	}

	static function createProduct($inputs) {
		$productId = self::$database->insert(
			"Product",
			[
				'productName' => $inputs['productName'],
				'productDescription' => $inputs['productDescription'],
				'primaryLocationId' => $inputs['primaryLocation'],
				'primaryBinId' => $inputs['primaryBin'],
				'secondaryLocationId' => $inputs['secondaryLocation'],
				'secondaryBinId' => $inputs['secondaryBin']
			]
		);

		return $productId['id'];
	}

	static function updateProduct($inputs) {
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
			['productId' => $inputs['productId']]
		);
	}

	static function deleteProduct($productId) {
		self::$database->delete(
			"Product",
			['productId' => $productId]
		);
	}

	static function readOnHand($productId) {
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
			LEFT JOIN Location ON Location.locationId = OnHand.locationId
			LEFT JOIN Bin ON Bin.binId = OnHand.binId
			LEFT JOIN Product ProductPrimaryDesignation ON ProductPrimaryDesignation.productId = OnHand.productId AND ProductPrimaryDesignation.primaryBinId = OnHand.binId
			LEFT JOIN Product ProductSecondaryDesignation ON ProductSecondaryDesignation.productId = OnHand.productId AND ProductSecondaryDesignation.secondaryBinId = OnHand.binId
			WHERE OnHand.productId = ?
			ORDER BY quantity DESC
		", [$productId]);
	}

	static function readTransactionHistory($productId) {
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
			LEFT JOIN Bin FromBin ON FromBin.binId = Transaction.fromBinId
			LEFT JOIN Bin ToBin ON ToBin.binId = Transaction.toBinId
			LEFT JOIN Location FromLocation ON FromLocation.locationId = Transaction.fromLocationId
			LEFT JOIN Location ToLocation ON ToLocation.locationId = Transaction.toLocationId
			WHERE Transaction.productId = ?
			ORDER BY transactionDate DESC
		", [$productId]);
	}

}