<?php

namespace Libraries;

use Agile\AgileLibrary;
use Dev;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use SmartTruncate;

class Excel extends AgileLibrary{

	private static function formatTrimLeadingZeros($value){
		return ltrim(trim($value), '0');
	}
	private static function formatCurrency($value){
		return '$'.number_format($value, 2);
	}
	private static function formatCurrencyDollars($value){
		return '$'.number_format($value, 0);
	}
	private static function formatTruncate($value){
		return SmartTruncate::truncate($value);
	}
	private static function formatTruncateAbs($value){
		return SmartTruncate::truncate(abs($value));
	}
	private static function formatPercent($value){
		return SmartTruncate::truncate($value).'%';
	}
	private static function getHyperlinkDomain(){
		if(Dev::isDev()){
			return 'http://dev.local/';
		}else{
			return 'http://itx166.fwe.com/';
		}
	}
	private static function getLinkStyle(){
		return array(
			'font'  => array(
				'color' => array('rgb' => '0000FF'),
				'underline' => Font::UNDERLINE_SINGLE
			)
		);
	}
	static function saveExcelFile($phpSpreadsheet, $file){
		$objWriter = IOFactory::createWriter($phpSpreadsheet,"Xlsx");
		$objWriter->save($file);
	}
	static function dumpExcelFile($phpSpreadsheet, $fileName){

		//header('Content-Type: application/vnd.ms-excel');
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		header('Content-Disposition: attachment;filename="'.$fileName.'.xlsx"');

		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		// If you're serving to IE over SSL, then the following may be needed
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0

		$objWriter = IOFactory::createWriter($phpSpreadsheet,"Xlsx");
		$objWriter->save('php://output');
	}
	static function outputExcelViaSpec($outputSpec, $allRows, $reportParams=[], $excelReportName='Excel Export'){

		$phpSpreadsheet = self::prepareExcelViaSpec($outputSpec, $allRows, $reportParams);

		self::dumpExcelFile($phpSpreadsheet, $excelReportName);
	}
	static function prepareExcelViaSpec($outputSpec, $allRows, $reportParams=[] ){

		$classLoader = new SplClassLoader('MyCLabs', $_SERVER['DOCUMENT_ROOT'].'/Libraries/PhpOffice/Psr');
		$classLoader->register();

		$classLoader = new SplClassLoader('ZipStream', $_SERVER['DOCUMENT_ROOT'].'/Libraries/PhpOffice/Psr');
		$classLoader->register();

		$classLoader = new SplClassLoader('PhpOffice', $_SERVER['DOCUMENT_ROOT'].'/Libraries/PhpOffice');
		$classLoader->register();

		$classLoader = new SplClassLoader('Psr', $_SERVER['DOCUMENT_ROOT'].'/Libraries/PhpOffice');
		$classLoader->register();

		$formatsToExcel = array(
			'formatCurrency' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE, // 2 decimals
			'formatCurrencyDollars' => NumberFormat::FORMAT_CURRENCY_USD, //no decimals
			'formatPercent' => NumberFormat::FORMAT_PERCENTAGE,
			'formatText' => NumberFormat::FORMAT_TEXT,
			'formatNumberCommas' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
		);

		$excelHeaders = array();
		$headerRow = array();
		foreach ($outputSpec as $column) {
			$headerRow[] = $column['text'];
		}
		$excelHeaders[] = $headerRow;


		$excelData = array();
		foreach($allRows as $row){
			$rowOutput = array();
			foreach ($outputSpec as $column) {
				if(isset($column['dataFunction']) ){
					$columnData = $column['dataFunction']($row);
				}else{
					$columnData = $row[$column['dataCol']];
				}

				if(isset($column['dataFormatter']) ){
					if(is_callable($column['dataFormatter'])){
						$columnData = $column['dataFormatter']($columnData);
					}else{
						$fnName = $column['dataFormatter'];
						$columnData = self::$fnName($columnData);
					}
				}
				$rowOutput[] = $columnData;
			}
			$excelData[] = $rowOutput;
		}


		$phpSpreadsheet = new Spreadsheet();
		$activeSheet = $phpSpreadsheet->getActiveSheet();
		$activeSheet->fromArray($excelHeaders, NULL, 'A1');
		$activeSheet->fromArray($excelData, NULL, 'A2');


		$rowCount = count($excelData);
		$headerCount = count($excelHeaders);
		$rowAndHeaderCount = $rowCount + $headerCount;


		//iterate data and columns to insert links
		$rowIndex = 1 + $headerCount;
		foreach($allRows as $row) {
			$columnIterator = 1;
			foreach ($outputSpec as $column) {
				if(isset($column['link']) || isset($column['excelCellStyle']) ) {
					$columnLetter = Coordinate::stringFromColumnIndex($columnIterator);
				}

				if(isset($column['link']) ){
					$linkUrl = $column['link']($row, $reportParams);
					if($linkUrl !== NULL){
						if(substr($linkUrl,0,4) !== 'http'){
							$linkUrl = self::getHyperlinkDomain() . $linkUrl;
						}
						$activeSheet->getCell($columnLetter . $rowIndex)->getHyperlink()->setUrl( $linkUrl );
					}
				}
				if(isset($column['excelCellStyle']) ) {
					$column['excelCellStyle']($activeSheet->getStyle($columnLetter . $rowIndex), $row, $reportParams);
				}
				$columnIterator++;
			}
			$rowIndex++;
		}


		//iterate columns to apply styles
		$columnIterator = 1;
		$rowIndex = 1 + $headerCount;
		foreach ($outputSpec as $column) {
			$style = null;

			$columnLetter = Coordinate::stringFromColumnIndex($columnIterator);
			if(isset($column['columnWidth'])){
				$activeSheet->getColumnDimension($columnLetter)->setWidth($column['columnWidth']);
			}else{
				$activeSheet->getColumnDimension($columnLetter)->setAutoSize(true);
			}

			if(isset($column['outputFormatter']) && isset($formatsToExcel[$column['outputFormatter']]) ){
				$style = $activeSheet->getStyle("{$columnLetter}{$rowIndex}:{$columnLetter}{$rowAndHeaderCount}");
				$style->getNumberFormat()->setFormatCode($formatsToExcel[$column['outputFormatter']]);
				//echo "{$columnLetter}{$rowIndex}:{$columnLetter}{$rowAndHeaderCount}<BR>\r\n";
			}

			if(isset($column['link']) ) {
				if (!$style) { //cache style reference lookups
					$style = $activeSheet->getStyle("{$columnLetter}{$rowIndex}:{$columnLetter}{$rowAndHeaderCount}");
				}
				$style->applyFromArray(self::getLinkStyle());
			}

			$columnIterator++;
		}

		$activeSheet->freezePane('A2');
		$activeSheet->setSelectedCell('A2');

		return $phpSpreadsheet;

	}
	static function outputTableViaSpec($outputSpec, $allRows, $reportParams=[]){

		$tableOutputString = "<TABLE border=1 cellpadding=4 cellspacing=0>\r\n";
		$tableOutputString .= "<TR>\r\n";
		foreach ($outputSpec as $column) {
			$tableOutputString .= "<TD>{$column['text']}</TD>\r\n";
		}
		$tableOutputString .= "</TR>\r\n";

		foreach($allRows as $row){
			$tableOutputString .= "<TR>\r\n";
			foreach ($outputSpec as $column) {
				if(isset($column['dataFunction']) ){
					$columnData = $column['dataFunction']($row);
				}else{
					$columnData = $row[$column['dataCol']];
				}

				if(isset($column['dataFormatter']) ){
					if(is_callable($column['dataFormatter'])){
						$columnData = $column['dataFormatter']($columnData);
					}else{
						$fnName = $column['dataFormatter'];
						$columnData = self::$fnName($columnData);
					}
				}
				if(isset($column['outputFormatter']) ){
					$fnName = $column['outputFormatter'];
					$columnData = self::$fnName($columnData);
				}

				if(isset($column['link']) ){
					$linkUrl = $column['link']($row, $reportParams);
					if($linkUrl !== NULL){
						if(substr($linkUrl,0,4) !== 'http'){
							$linkUrl = self::getHyperlinkDomain() . $linkUrl;
						}
						$columnData = "<a href=\"".$linkUrl."\">{$columnData}</a>";
					}
				}
				$cellstyle = "";
				if( isset($column['tableCellStyle']) ) {
					$cellstyle = $column['tableCellStyle']($row);
				}
				$tableOutputString .= "<TD {$cellstyle}>{$columnData}</TD>\r\n";
			}
			$tableOutputString .= "</TR>\r\n";
		}
		$tableOutputString .= "</TABLE>\r\n";

		return $tableOutputString;
	}

	static function outputArrayViaSpec($outputSpec, $allRows){

		$outputArray = array();

		foreach($allRows as $row){
			$outputRow = array();
			foreach ($outputSpec as $column) {
				if(isset($column['dataFunction']) ){
					$columnData = $column['dataFunction']($row);
				}else{
					$columnData = $row[$column['dataCol']];
				}

				if(isset($column['dataFormatter']) ){
					if(is_callable($column['dataFormatter'])){
						$columnData = $column['dataFormatter']($columnData);
					}else{
						$fnName = $column['dataFormatter'];
						$columnData = self::$fnName($columnData);
					}
				}

				$outputRow[] = $columnData;
			}
			$outputArray[] = $outputRow;
		}

		return $outputArray;
	}

	static function backgroundColorStyle($backgroundRgbColor){
		return array(
			'fill' => array(
				'fillType' => Fill::FILL_SOLID,
				'color' => array('rgb' => $backgroundRgbColor)
			)
		);
	}
}