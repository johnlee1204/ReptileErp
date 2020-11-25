<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/inc/php/libraries/PHPExcel-1.8.1/Classes/PHPExcel.php');

/** @deprecated use Libraries/Excel instead! */
class ExcelExporter{

    /*
     * Config
     *      name (without extension)
     *      data
     *      headers
     *      numberFormats  //Assoc array of columns, or
     */
    static function export($config){
        $phpExcel = new PHPExcel();
        $activeSheet = $phpExcel->getActiveSheet();
        $activeSheet->fromArray($config['headers'], NULL, 'A1');
        $rows = count($config['data']);
        $activeSheet->fromArray($config['data'], NULL, 'A2');

        $validFormats = array(
            'text' => PHPExcel_Style_NumberFormat::FORMAT_TEXT,
            'currencyUsd' => PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE
        );

		  if(!isset($config['numberFormats'])){
			  $config['numberFormats'] = array();
		  }

        foreach($config['numberFormats'] as $column => $format){
            if(!key_exists($format,$validFormats)){
                continue;
            }
            if(false !== $searchResult = array_search($column,$config['headers'])){
                $column = PHPExcel_Cell::stringFromColumnIndex($searchResult);
            }
            $activeSheet->getStyle($column.'2:'.$column.($rows+2))->getNumberFormat()->setFormatCode($validFormats[$format]);
        }
        $activeSheet->setSelectedCell('A2');


        $lastColumn = $activeSheet->getHighestColumn();
        $lastColumn++;
        //PHP will increment letters just like Excel's columns. Sp0oKy.
        for ($column = 'A'; $column != $lastColumn; $column++) {
            $activeSheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Redirect output to a client’s web browser (Excel5)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$config['name'].'.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

}
