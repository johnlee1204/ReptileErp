<?php


use Satisfactory\Models\SatisfactoryModel;

class Satisfactory extends AgileBaseController
{
	function searchParts() {
		$input = Validation::validateJsonInput([
			'partName'
		]);

		$this->outputSuccessData(SatisfactoryModel::searchParts($input['partName']));
	}

	function readPartsCombo() {
		$input = Validation::validatePost([
			'partName' => 'notBlank'
		]);

		$this->outputSuccessData(SatisfactoryModel::readPartsCombo($input['partName']));
	}

	function readPart() {
		$input = Validation::validateJsonInput([
			'partId' => 'numeric'
		]);

		$this->outputSuccessData(SatisfactoryModel::readPart($input['partId']));

	}
	function createPart() {
		$inputs = Validation::validateJsonInput([
			'partName' => 'notBlank',
			'partDescription',
			'partsPerMinute'
		]);

		if(SatisfactoryModel::readPartByName($inputs['partName']) !== NULL) {
			throw new AgileUserMessageException("Part: " . $inputs['partName'] . " already exists!");
		}

		SatisfactoryModel::createPart($inputs);

		$newPartId = SatisfactoryModel::readLastPartMade();

		$this->outputSuccessData($newPartId);
	}

	function updatePart() {
		$inputs = Validation::validateJsonInput([
			'partId' => 'numeric',
			'partName' => 'notBlank',
			'partDescription',
			'partsPerMinute'
		]);

		SatisfactoryModel::updatePart($inputs);

		$this->outputSuccess();
	}

	function deletePart() {
		$input = Validation::validateJsonInput([
			'partId' => 'numeric'
		]);

		SatisfactoryModel::deletePart($input['partId']);
		$this->outputSuccess();
	}

	function readPartBom() {
		$input = Validation::validateJsonInput([
			'partId' => 'numeric'
		]);

		$this->outputSuccessData(SatisfactoryModel::readPartBom($input['partId']));
	}

	function readPartBomRecord() {
		$input = Validation::validateJsonInput([
			'bomId' => 'numeric'
		]);

		$this->outputSuccessData(SatisfactoryModel::readPartBomRecord($input['bomId']));
	}

	function createBomRecord() {
		$inputs = Validation::validateJsonInput([
			'bomPart' => 'numeric',
			'parentPart' => 'numeric',
			'quantity' => 'numeric'
		]);

		SatisfactoryModel::createBomRecord($inputs);

		$newBomId = SatisfactoryModel::readLastBomRecordMade();

		$this->outputSuccessData($newBomId);
	}

	function updateBomRecord() {
		$inputs = Validation::validateJsonInput([
			'bomId' => 'numeric',
			'bomPart' => 'numeric',
			'parentPart' => 'numeric',
			'quantity' => 'numeric'
		]);

		SatisfactoryModel::updateBomRecord($inputs);

		$this->outputSuccess();
	}

	function deleteBomRecord() {
		$input = Validation::validateJsonInput([
			'bomId' => 'numeric'
		]);

		SatisfactoryModel::deleteBomRecord($input['bomId']);
		$this->outputSuccess();
	}

	function readRoutings() {
		$input = Validation::validateJsonInput([
			'partId' => 'numeric'
		]);

		$this->outputSuccessData(SatisfactoryModel::readRoutings($input['partId']));

	}

	function readRouting() {
		$input = Validation::validateJsonInput([
			'routingId' => 'numeric'
		]);

		$this->outputSuccessData(SatisfactoryModel::readRouting($input['routingId']));

	}

	function createRouting() {
		$inputs = Validation::validateJsonInput([
			'workcenter' => 'notBlank',
			'partId' => 'numeric'
		]);

		SatisfactoryModel::createRouting($inputs);

		$newRoutingId = SatisfactoryModel::readLastRoutingMade();

		$this->outputSuccessData($newRoutingId);
	}

	function updateRouting() {
		$inputs = Validation::validateJsonInput([
			'routingId' => 'numeric',
			'workcenter' => 'notBlank',
			'partId' => 'numeric'
		]);

		SatisfactoryModel::updateRouting($inputs);

		$this->outputSuccess();
	}

	function deleteRouting() {
		$input = Validation::validateJsonInput([
			'routingId' => 'numeric'
		]);

		SatisfactoryModel::deleteRouting($input['routingId']);
		$this->outputSuccess();
	}

	function createManufacturingLayout() {
		$input = Validation::validateGet([
			'partId' => 'numeric'
		]);

		$part = SatisfactoryModel::readPart($input['partId']);
		$routing = SatisfactoryModel::readRoutings($input['partId']);

		if(count($routing) !== 0) {
			$routing = $routing[0];
		}

		$workcenters = [$routing[1] => 1];

		$energy = $routing[3];

		$output = [
			'partName' => $part['partName'],
			'routing' => $routing[1],
			'BOM' => []
		];




		$this->createManufacturingLayoutRecursive($input['partId'], $output['BOM'], $workcenters, $energy);
		if(isset($_GET['showworkcenters'])  && $_GET['showworkcenters'] === "1") {
			$showWorkcenters = '&showworkcenters=0';
			echo <<<EOT
 <button onclick='window.location.href = window.location.href + "{$showWorkcenters}"'>Hide Workcenters</button>
 EOT;
		} else {
			$showWorkcenters = '&showworkcenters=1';
			echo <<<EOT
 <button onclick='window.location.href = window.location.href + "{$showWorkcenters}"'>Show Workcenters</button>
 EOT;
		}



		echo "<pre>";print_r($workcenters);
		echo '<BR>';
		echo "Energy: " . $energy . ' MW';
		echo '<BR>';

		$outputHtml = "<style>
/*Now the CSS*/
* {margin: 0; padding: 0;}

.tree ul {
	padding-top: 40px; position: relative;
	
	transition: all 0.5s;
	-webkit-transition: all 0.5s;
	-moz-transition: all 0.5s;
}

.tree li {
	float: left; text-align: center;
	list-style-type: none;
	position: relative;
	padding: 40px 5px 0 5px;
	
	transition: all 0.5s;
	-webkit-transition: all 0.5s;
	-moz-transition: all 0.5s;
}

/*We will use ::before and ::after to draw the connectors*/

.tree li::before, .tree li::after{
	content: '';
	position: absolute; top: 0; right: 50%;
	border-top: 1px solid #ccc;
	width: 50%; height: 40px;
}
.tree li::after{
	right: auto; left: 50%;
	border-left: 1px solid #ccc;
}

/*We need to remove left-right connectors from elements without 
any siblings*/
.tree li:only-child::after, .tree li:only-child::before {
	display: none;
}


/*Remove space from the top of single children*/
.tree li:only-child{ padding-top: 0;}

/*Remove left connector from first child and 
right connector from last child*/
.tree li:first-child::before, .tree li:last-child::after{
	border: 0 none;
}

/*Adding back the vertical connector to the last nodes*/
.tree li:last-child::before{
	border-right: 1px solid #ccc;
	border-radius: 0 5px 0 0;
	-webkit-border-radius: 0 5px 0 0;
	-moz-border-radius: 0 5px 0 0;
}
.tree li:first-child::after{
	border-radius: 5px 0 0 0;
	-webkit-border-radius: 5px 0 0 0;
	-moz-border-radius: 5px 0 0 0;
}

/*Time to add downward connectors from parents*/
.tree ul ul::before{
	content: '';
	position: absolute; top: 0; left: 50%;
	border-left: 1px solid #ccc;
	width: 0; height: 40px;
}

.tree li a{
	border: 1px solid #ccc;
	padding: 5px 10px;
	text-decoration: none;
	color: #666;
	font-family: arial, verdana, tahoma;
	font-size: 11px;
	display: inline-block;
	
	border-radius: 5px;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	
	transition: all 0.5s;
	-webkit-transition: all 0.5s;
	-moz-transition: all 0.5s;
}

/*Time for some hover effects*/
/*We will apply the hover effect the the lineage of the element also*/
.tree li a:hover, .tree li a:hover+ul li a {
	background: #c8e4f8; color: #000; border: 1px solid #94a0b4;
}
/*Connector styles on hover*/
.tree li a:hover+ul li::after, 
.tree li a:hover+ul li::before, 
.tree li a:hover+ul::before, 
.tree li a:hover+ul ul::before{
	border-color:  #94a0b4;
}
</style>";
		$outputHtml .= "<div class = 'tree'><ul><li><a href = '#'>{$output['partName']}</a>";
		$id = 1;
		$this->drawLayout($output, $outputHtml, $id);

		$outputHtml .= "</li></ul>";
		$outputHtml .= "</div>";

		echo '<BR>';
		echo "<pre>";echo($outputHtml);
	}

	function createManufacturingLayoutRecursive($partId, &$childrenArray, &$workcenters, &$energy) {
		$children = $this->database->fetch_all_assoc("
			SELECT 
				Part.partId,
				Part.partName
			FROM BillOfMaterial
			JOIN Part on Part.partId = BillOfMaterial.partId 
			WHERE BillOfMaterial.parentPartId = ?
		", [$partId]);

		foreach($children as $child) {
			$routing = SatisfactoryModel::readRoutings($child['partId']);

			$workcenter = NULL;
			$routingEnergy = 0;
			if(count($routing) !== 0) {
				$routing = $routing[0];
				$workcenter = $routing[1];
				$routingEnergy = $routing[3];
			}

			$childrenArray[] = [
				'partName' => $child['partName'],
				'routing' => $workcenter,
				'BOM' => []
			];

			if($workcenter !== NULL) {
				if(!isset($workcenters[$routing[1]])) {
					$workcenters[$routing[1]] = 1;
				} else {
					$workcenters[$routing[1]] += 1;
				}
			}

			$energy += $routingEnergy;

			$this->createManufacturingLayoutRecursive($child['partId'], $childrenArray[count($childrenArray) - 1]['BOM'], $workcenters, $energy);
		}
	}

	function drawLayout($output, &$outputHtml, &$id) {
		if(isset($_GET['showworkcenters'])  && $_GET['showworkcenters'] === "1") {
			$routing = $output['routing'];
			$outputHtml .= "<style>
			.tree ul.a{$id}::before{
				content: '$routing' !important;
			}
			</style>";
		}

		$outputHtml .= "<ul class = 'a{$id}'>";


		foreach($output['BOM'] as $child) {

			$outputHtml .= "<li><a href='#'>" . $child['partName'] . "</a>";
			$id = $id + 1;
			$this->drawLayout($child, $outputHtml, $id);

			$outputHtml .= "</li>";
			$outputHtml .= "</ul>";
		}
	}

}