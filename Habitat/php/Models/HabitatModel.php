<?php

namespace Habitat\Models;
use AgileModel;

class HabitatModel extends AgileModel {
	static function updateHabitatVital($temperature, $humidity, $habitatId) {
		self::$database->query("
			INSERT INTO HabitatVital(temperature, humidity, habitatId)
			VALUES(?, ?, ?)
		", [$temperature, $humidity, $habitatId]);
	}

	static function readHabitatVitals($habitatId) {
		$records = self::$database->fetch_all_assoc("
			SELECT
				recordDate x,
				temperature y,
				humidity z
			FROM HabitatVital
			WHERE
				habitatId = ?
			ORDER BY recordDate DESC
			LIMIT 100
		", [$habitatId]);

		$tempRecordsFormatted = [];
		$output = [];
		$count = 100;

		foreach($records as $record) {
			$record['x'] = $count;
			$record['y'] = floatval($record['y']);
			$tempRecordsFormatted[] = ['x' => $record['x'], 'y' => $record['y']];
			$count--;
		}

		$tempRecordsFormatted = array_reverse($tempRecordsFormatted);
		$output['temps'] = $tempRecordsFormatted;

		$humidityRecordFormatted = [];

		$count = 100;
		foreach($records as $record) {
			$record['x'] = $count;
			$record['y'] = floatval($record['z']);
			$humidityRecordFormatted[] = ['x' => $record['x'], 'y' => $record['y']];
			$count--;
		}
		$humidityRecordFormatted = array_reverse($humidityRecordFormatted);

		$output['humidities'] = $humidityRecordFormatted;

		return $output;
	}

	static function readHabitats() {
		return self::$database->fetch_all_row("
			SELECT
				habitatId,
				habitatName
			FROM Habitat
			ORDER BY habitatName
		");
	}

	static function readHabitat($habitatId) {
		return self::$database->fetch_assoc("
			SELECT
				habitatName,
				rack
			FROM Habitat
			WHERE
				habitatId = ?
		", [$habitatId]);
	}

	static function createHabitat($inputs) {
		self::$database->query("
			INSERT INTO Habitat(habitatName, rack)
			VALUES(?, ?)
		", [$inputs['habitatName'], $inputs['rack']]);

		return self::$database->fetch_assoc("
			SELECT
				MAX(habitatId) maxId
			FROM Habitat
		")['maxId'];
	}

	static function updateHabitat($inputs) {
		self::$database->query("
			UPDATE Habitat
			SET
				habitatName = ?,
				rack = ?
			WHERE
				habitatId = ?
		", [$inputs['habitatName'], $inputs['rack'], $inputs['habitatId']]);
	}

	static function deleteHabitat($habitatId) {
		self::$database->query("
			DELETE FROM Habitat
			WHERE
				habitatId = ?
		", [$habitatId]);
	}

}