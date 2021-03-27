
CREATE DATABASE IF NOT EXISTS `AgileInventory` DEFAULT
USE `AgileInventory`;

CREATE TABLE `AccessToken` (
  `shop` varchar(100) NOT NULL,
  `accessToken` varchar(256) NOT NULL
)

CREATE TABLE `Bin` (
  `binId` int NOT NULL,
  `binName` varchar(100) NOT NULL,
  `binDescription` varchar(1000) NOT NULL,
  `locationId` int NOT NULL,
  `shop` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
)

CREATE TABLE `Component` (
  `componentId` int NOT NULL,
  `productId` int NOT NULL,
  `parentProductId` int NOT NULL,
  `quantity` int NOT NULL,
  `shop` varchar(100) NOT NULL
)

CREATE TABLE `Facility` (
  `facilityId` int NOT NULL,
  `facilityName` varchar(100) NOT NULL,
  `facilityDescription` varchar(1000) NOT NULL,
  `address1` varchar(5000) DEFAULT NULL,
  `address2` varchar(5000) DEFAULT NULL,
  `city` varchar(200) DEFAULT NULL,
  `zip` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `shopifyId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `shop` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
)

CREATE TABLE `Location` (
  `locationId` int NOT NULL,
  `locationName` varchar(100) NOT NULL,
  `locationDescription` varchar(1000) NOT NULL,
  `facilityId` int NOT NULL,
  `shop` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
)


CREATE TABLE `OnHand` (
  `onHandId` int NOT NULL,
  `productId` int NOT NULL,
  `quantity` float NOT NULL,
  `locationId` int NOT NULL,
  `binId` int NOT NULL,
  `shop` varchar(100) NOT NULL
)

CREATE TABLE `Product` (
  `productId` int NOT NULL,
  `productName` varchar(200) NOT NULL,
  `productDescription` varchar(1000) NOT NULL,
  `primaryLocationId` int DEFAULT NULL,
  `primaryBinId` int DEFAULT NULL,
  `secondaryLocationId` int DEFAULT NULL,
  `secondaryBinId` int DEFAULT NULL,
  `shopifyProductId` varchar(100) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `onWebsite` bit(1) NOT NULL,
  `shop` varchar(100) NOT NULL
)

CREATE TABLE `Session` (
  `sessionId` varchar(256) NOT NULL,
  `shop` varchar(100) NOT NULL
)

CREATE TABLE `SetupNonce` (
  `id` varchar(300) NOT NULL,
  `nonce` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
)

CREATE TABLE `Shop` (
  `shop` varchar(100) NOT NULL,
  `shopName` varchar(100) NOT NULL,
  `shopifyId` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL
)

CREATE TABLE `Transaction` (
  `transactionId` int NOT NULL,
  `productId` int NOT NULL,
  `fromLocationId` int DEFAULT NULL,
  `fromBinId` int DEFAULT NULL,
  `toLocationId` int DEFAULT NULL,
  `toBinId` int DEFAULT NULL,
  `transactionDate` datetime NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `comment` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `quantity` float NOT NULL,
  `shop` varchar(100) NOT NULL
)

ALTER TABLE `Bin`
  ADD PRIMARY KEY (`binId`);

ALTER TABLE `Component`
  ADD PRIMARY KEY (`componentId`);

ALTER TABLE `Facility`
  ADD PRIMARY KEY (`facilityId`);

ALTER TABLE `Location`
  ADD PRIMARY KEY (`locationId`);

ALTER TABLE `OnHand`
  ADD PRIMARY KEY (`onHandId`);

ALTER TABLE `Product`
  ADD PRIMARY KEY (`productId`);

ALTER TABLE `Transaction`
  ADD PRIMARY KEY (`transactionId`);

ALTER TABLE `Bin`
  MODIFY `binId` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Component`
  MODIFY `componentId` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Facility`
  MODIFY `facilityId` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Location`
  MODIFY `locationId` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `OnHand`
  MODIFY `onHandId` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Product`
  MODIFY `productId` int NOT NULL AUTO_INCREMENT;

ALTER TABLE `Transaction`
  MODIFY `transactionId` int NOT NULL AUTO_INCREMENT;
COMMIT;
