<?php

use PetMaster\Models\PetMasterModel;
use PetMaster\Tables\PetMasterLog;

class PetMaster extends AgileBaseController {

	static $AgilePermissions = [
		'index' => 'read',
		'readReptiles' => 'read',
		'readMorphs' => 'read',
		'readPet' => 'read',
		'createPet' => 'create',
		'updatePet' => 'update',
		'deletePet' => 'delete',
		'searchPets' => 'read',
		'readPetAttachments' => 'read',
		'readAttachment' => 'read',
		'uploadAttachment' => 'update',
		'deleteAttachment' => 'delete',
		'readBreedingData' => 'read',
		'createBreedingPair' => 'create',
		'readParentOptions' => 'read'
	];

	static $allowedExtensions = [
		'jpg',
		'jpeg',
		'png'
	];

	function readReptiles() {
		$this->outputSuccessData(PetMasterModel::readReptiles());
	}

	function readMorphs() {
		$this->outputSuccessData(PetMasterModel::readMorphs());
	}

	function readPet() {
		$input = Validation::validateJsonInput([
			'petId' => 'numeric'
		]);

		$this->outputSuccessData(PetMasterModel::readPet($input['petId']));
	}

	function createPet() {
		$inputs = Validation::validateJsonInput([
			'serial' => 'notBlank',
			'type' => 'notBlank',
			'price' => 'numericOrNull',
			'sex' => 'notBlank',
			'status',
			'birthDate',
			'receiveDate' => 'notBlank',
			'sellDate',
			'vendor',
			'cost',
			'habitatId' => 'numericOrNull',
			'food',
			'feedingQuantity',
			'feedingFrequency',
			'customer',
			'notes',
			'weight',
			'sellPrice',
			'morph' => 'numericOrNull',
			'maleParent' => 'numericOrNull',
			'femaleParent' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		$newId = PetMasterModel::createPet($inputs);
		$this->database->commit_transaction();

		$this->outputSuccessData($newId);
	}

	function updatePet() {
		$inputs = Validation::validateJsonInput([
			'petId' => 'numeric',
			'serial' => 'notBlank',
			'type' => 'notBlank',
			'price' => 'numericOrNull',
			'sex' => 'notBlank',
			'status',
			'birthDate',
			'receiveDate' => 'notBlank',
			'sellDate',
			'vendor',
			'cost',
			'habitatId' => 'numericOrNull',
			'food',
			'feedingQuantity',
			'feedingFrequency',
			'customer',
			'notes',
			'weight',
			'sellPrice',
			'morph' => 'numericOrNull',
			'maleParent' => 'numericOrNull',
			'femaleParent' => 'numericOrNull'
		]);

		$this->database->begin_transaction();
		PetMasterModel::updatePet($inputs);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function deletePet() {
		$input = Validation::validateJsonInput([
			'petId' => 'numeric'
		]);

		$this->database->begin_transaction();
		PetMasterModel::deletePet($input['petId']);
		$this->database->commit_transaction();

		$this->outputSuccess();
	}

	function searchPets() {
		$inputs = Validation::validateJsonInput([
			'serial',
			'type',
			'receiveDate',
			'sellDate',
		]);

		$this->outputSuccessData(PetMasterModel::searchPets($inputs));
	}

	function readPetAttachments() {
		$input = Validation::validateJsonInput(array(
			'petId' => 'numeric'
		));
		$attachments = PetMasterModel::readPetAttachments($input['petId']);

		$this->outputSuccessData($attachments);
	}

	function readAttachment() {
		$input = Validation::validateGet([
			'petAttachmentId'
		]);

		$petAttachment = PetMasterModel::readAttachment($input['petAttachmentId']);

		echo file_get_contents($petAttachment['fileLocation'] . $petAttachment['fileName']);
	}

	public function uploadAttachment()
	{
		$input = Validation::validateGet(array(
			'petId' => 'numeric'
		));

		$sizeLimitMb = 50;
		$sizeLimit = $sizeLimitMb * 1024 * 1024; //convert to bytes

		if (!empty($_FILES['fd-file']) and is_uploaded_file($_FILES['fd-file']['tmp_name'])) {
			// Regular multipart/form-data upload.

			$uploadedFileSize = $_FILES['fd-file']['size'];
			$name = $_FILES['fd-file']['name'];
			$data = file_get_contents($_FILES['fd-file']['tmp_name']);

		} elseif (isset($_SERVER['HTTP_X_FILE_NAME'])) {
			//drag and drop upload

			$uploadedFileSize = $_SERVER['HTTP_X_FILE_SIZE'];
			$name = urldecode($_SERVER['HTTP_X_FILE_NAME']);
			$data = file_get_contents("php://input"); // Raw POST data.
		} else {
			throw new AgileUserMessageException("No File Uploaded");
		}

		if ($uploadedFileSize >= $sizeLimit) {
			throw new AgileUserMessageException("File cannot be larger than " . $sizeLimitMb . "MB");
		}

		if (trim($name) == '') {
			throw new AgileUserMessageException('Upload file Header "X_FILE_NAME" not sent!');
		}

		$pathInfo = pathinfo($name);

		if (!in_array(strtolower($pathInfo['extension']), self::$allowedExtensions)) {
			throw new AgileUserMessageException("File extension \"{$pathInfo['extension']}\" not allowed!<BR>\r\n{$name}");
		}

		$fileDir = "/var/www/leesheet.com/public_html/images/pets/" . date("Y") . '/' . date("m") . '/';
		$databaseFileDir = "/var/www/leesheet.com/public_html/images/pets/" . date("Y") . '/' . date("m") . '/';

		$newFileName = $input['petId'];

		//cleanup multiple spaces and underscores, convert spaces to underscores:
		$newFileName = preg_replace('!\s+!', ' ', $newFileName);
		$newFileName = str_replace(' ', '_', $newFileName);
		$newFileName = preg_replace('!_+!', '_', $newFileName);

		if (file_exists($fileDir . $newFileName . '.' . $pathInfo['extension'])) {
			$tryCount = 1;
			do {
				$renamedFileName = $newFileName . '_' . str_pad($tryCount, 2, '0', STR_PAD_LEFT);
			} while (file_exists($fileDir . $renamedFileName . '.' . $pathInfo['extension']) && $tryCount++ <= 50);
			if ($tryCount >= 50) {
				throw new AgileUserMessageException("File name error. More than 50 files with same name.");
			}
			$newFileName = $renamedFileName;
		}
		$newFileName .= '.' . $pathInfo['extension'];

		if (!is_dir($fileDir)) {
			// dir doesn't exist, make it
			mkdir($fileDir, 0777, true);
		}
		if (FALSE == file_put_contents($fileDir . $newFileName, $data)) {
			throw new AgileUserMessageException('Error saving uploaded file!');
		}

		$date = date("Y-m-d");

		$this->database->query("
			INSERT INTO PetAttachment(petId, fileName, fileLocation, photoDate)
			VALUES(?,?,?,?)
		", [$input['petId'], $newFileName, $databaseFileDir, $date]);

		$this->outputSuccess(array("file" => $newFileName));
	}

	function deleteAttachment() {
		$input = Validation::validateJsonInput([
			'petAttachmentId' => 'numeric'
		]);

		$attachment = PetMasterModel::readAttachment($input['petAttachmentId']);

		if($attachment === NULL) {
			throw new AgileUserMessageException("Attachment not found!");
		}

		if(unlink($attachment['fileLocation'] . $attachment['fileName'])) {
			$this->database->query("DELETE FROM PetAttachment WHERE petAttachmentId = ?", [$input['petAttachmentId']]);
		} else {
			throw new AgileUserMessageException("Failed to Delete Attachment!");
		}

		$this->outputSuccess();
	}

	function readBreedingData() {
		$input = Validation::validateJsonInput([
			'reptileId' => 'numeric'
		]);

		$this->outputSuccess([
			'breedWith' => PetMasterModel::readCanBreedWith($input['reptileId']),
			'currentlyBreedingWith' => PetMasterModel::readCurrentlyBreedingWith($input['reptileId'])
		]);
	}

	function createBreedingPair() {
		$inputs = Validation::validateJsonInput([
			'reptileId1' => 'numeric',
			'reptileId2' => 'numeric'
		]);

		PetMasterModel::createBreedingPair($inputs['reptileId1'], $inputs['reptileId2']);

		$this->outputSuccess();
	}

	function readParentOptions() {
		$this->outputSuccess(PetMasterModel::readParentOptions());
	}
}