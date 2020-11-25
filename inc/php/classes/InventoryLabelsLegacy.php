<?php

    require_once($_SERVER['DOCUMENT_ROOT'].'/inc/php/classes/sql_safe.php' );
    require_once($_SERVER['DOCUMENT_ROOT'].'/inc/php/classes/NoCaching.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/inc/php/classes/FoxProRunReport.php');

    NoCaching::disableCaching();

    class InventoryLabelsLegacy{

        public $physicalInventoryTagPrefix = '905';
        public $partLocationBinBarcodePrefix = '001';
        public static $currentLabelRev = '2';

        static function getDefaultInventoryPrinter(){
            return "TN INV 4x4 .205";
        }

        function getPartBinLocBarcode($dbc, $part, $partRev, $loc, $bin){

    // Check if the part, loc, bin barcode exists in the barcodes table
            $sql = "select barcodeId, type from fwe_dev..barcodes where val1='".sql_safe($part)."' and val2='".sql_safe($partRev)."' and val3='".sql_safe($loc)."' and val4='".sql_safe($bin)."'";

            if( FALSE === $dbc->query($sql ) ){
                die('{success:false,errors:{desc:"Error checking if barcode exists! '.$dbc->getLastMessage().'"}}');
            }
    //get the barcode, if it wasn't found, create a record
            if( $dbc->num_rows() > 0 ){

                $row = $dbc->fetch_assoc();
                //$barcode = str_pad($row['barcodeId'], 3, '0', STR_PAD_LEFT) . $row['barcodeId'];
                return trim($row['barcodeId']);

            }else{

                $sql = "INSERT INTO fwe_dev..barcodes (type, val1, val2, val3, val4) VALUES ('{$this->partLocationBinBarcodePrefix}', '".sql_safe($part)."','".sql_safe($partRev)."', '".sql_safe($loc)."', '".sql_safe($bin)."')";
                if( FALSE === $dbc->query($sql ) ){

                    die('{success:false,errors:{desc:"Error 1 allocating new barcode! '.$dbc->getLastMessage().'"}}');
                }
                if( $dbc->affected_rows() < 1 ){

                    die('{success:false,errors:{desc:"Error 2 allocating new barcode! '.$dbc->getLastMessage().'"}}');
                }


                $sql = "SELECT @@IDENTITY";
                if( FALSE === $dbc->query($sql ) ){

                    die('{success:false,errors:{desc:"Error 3 allocating new barcode! '.$dbc->getLastMessage().'"}}');
                }
                if( $dbc->num_rows() < 1 ){

                    die('{success:false,errors:{desc:"Error 4 allocating new barcode! '.$dbc->getLastMessage().'"}}');

                }
                $row = $dbc->fetch_array();
                return trim($row[0]);
            }
      }
        function printPartLocBinLabel($dbc){

            $printJobName = "Part, Location, Bin Label";

            require($_SERVER['DOCUMENT_ROOT'] . '/inc/php/classes/ConfigPrinters.php' );
            $printers = ConfigPrinters::getPrinters();

            if( !isset($_POST['printer']) || !array_key_exists($_POST['printer'], $printers) ){
                return "Missing parameters1";
                die('{success:false,errors:{desc:"Missing parameters"}}');
            }
            $printer = $printers[$_POST['printer']]['name'];

            if( !isset($_POST['part']) || !isset($_POST['partRev']) || !isset($_POST['location']) || !isset($_POST['bin']) ){
                return "Missing parameters";
                die('{success:false,errors:{desc:"Missing parameters"}}');
            }
            if( isset($_POST['numLabels']) && is_numeric($_POST['numLabels'])){
                $numLabels = $_POST['numLabels'];
            }else{
                $numLabels = 1;
            }
            if( $numLabels > 10 ){
                $numLabels = 10;
            }
            $part = strtoupper(trim($_POST['part']));
            $partSqlSafe = sql_safe($part);

            $partRev = strtoupper(trim($_POST['partRev']));
            $partRevSqlSafe = sql_safe($partRev);

            $loc = strtoupper(trim($_POST['location']));
            $locSqlSafe = sql_safe($loc);

            $bin = strtoupper(trim($_POST['bin']));
            $binSqlSafe = sql_safe($bin);

            // Check if the part exists in the item master
            // this is also used to get the part description
            $sql = "select fpartno, fdescript from m2mdata01..inmast where fpartno ='{$partSqlSafe}' and frev = '{$partRevSqlSafe}'";
            if( FALSE === $dbc->query($sql ) ){
                return 'Error checking if part exists! '.$dbc->getLastMessage();
                die('{success:false,errors:{desc:"Error checking if part exists! '.$dbc->getLastMessage().'"}}');
            }
            if( $dbc->num_rows() < 1 ){
                return 'This part does not exist';
                die('{success:false,errors:{desc:"This part does not exist"}}');
            }
            $row = $dbc->fetch_assoc();
            $partDescription = trim($row['fdescript']);

            $barcode = $this->partLocationBinBarcodePrefix . InventoryLabelsLegacy::getPartBinLocBarcode($dbc, $part, $partRev, $loc, $bin);

            $labelData = array(
                "part"=>$part,
                "partDesc"=>$partDescription,
                "loc"=>$loc,
                "bin"=>$bin,
                "barcode"=>"*".$barcode."*",
            );


            $date = date("Y_m_d_h_i_s");
            $tableName = "tmp_foxpro_location_bin_part_label_{$date}_".uniqid();

            $createSQL = "CREATE TABLE FWE_DEV..{$tableName}(
                        [part] [varchar](100) NULL,
                        [loc] [varchar](100) NULL,
                        [bin] [varchar](100) NULL,
                        [partDescription] [varchar](100) NULL,
                        [barcode] [varchar](100) NULL
                        ) ON [PRIMARY]
                        ";

            $dbc->query($createSQL);

            $insertSQL = "INSERT INTO FWE_DEV..{$tableName} (
                        part
                        ,loc
                        ,bin
                        ,partDescription
                        ,barcode
                        ) VALUES ";


            for($i=0;$i<$numLabels;$i++){
                if($i!==0){
                    $insertSQL .=",";
                }
                $insertSQL .="(
                '".sql_safe($labelData['part'])."',
                '".sql_safe($labelData['loc'])."',
                '".sql_safe($labelData['bin'])."',
                '".sql_safe($labelData['partDesc'])."',
                '".sql_safe($labelData['barcode'])."'
                )";
            }

            $dbc->query($insertSQL);


            $foxproReport = new FoxProRunReport(array(
                'mssqlHelperInstance' => $dbc
                ,'template' => 'location_bin_part_4x75.frx'
                ,'tableName' => 'fwe_dev..['.$tableName.']'
                ,'printerName' => $printer
                ,'cleanupTempTable'=> true
                ,'printerJobName'=> $printJobName
            ));

            if(FALSE == $foxproReport->printReport() ){
                die('{success:true,printed:false}');
            }

            die('{success:true,printed:true}');
        }

        function printPartLabel($dbc){

            require($_SERVER['DOCUMENT_ROOT'] . '/inc/php/classes/ConfigPrinters.php' );
            $printers = ConfigPrinters::getPrinters();

            if( !isset($_POST['printer']) || !array_key_exists($_POST['printer'], $printers) ){
                die('{success:false,errors:{desc:"Missing parameters"}}');
            }

            if( !isset($_POST['partId']) || !is_numeric($_POST['partId']) ){
                die('{success:false,errors:{desc:"Missing parameters"}}');
            }

            if( isset($_POST['numLabels']) && is_numeric($_POST['numLabels']) ){
                $numLabels = $_POST['numLabels'];
            }else{
                $numLabels = 1;
            }

            if( $numLabels > 10 ){
                $numLabels = 10;
            }

            $printer = $printers[$_POST['printer']]['name'];

            $printJobName = "Part Label";
            $Part_Barcode_Prefix = '004';

            $sql = "select rtrim( identity_column) as barcode,rtrim(fpartno) part, rtrim(fdescript) partDesc from m2mdata01..inmast where identity_column = '".sql_safe($_POST['partId'])."'";
            if( FALSE === $dbc->query($sql) ){
                die('{success:false,errors:{desc:"error querying data"}}');
            }
            if( $dbc->num_rows() < 1 ){
                die('{success:false,errors:{desc:"part not found in item master"}}');
            }
            $row = $dbc->fetch_assoc();

            $data = array(
                "part"=> $row['part'],
                "partDesc"=> $row['partDesc'],
                "barcode"=> "*".$Part_Barcode_Prefix . $row['barcode']."*",
            );

            $tableName = "tmp_foxpro_part_label_".uniqid();

            $createSQL = "CREATE TABLE FWE_DEV..{$tableName}(
[part] [varchar](100) NULL,
[partDescription] [varchar](100) NULL,
[barcode] [varchar](100) NULL
) ON [PRIMARY]
";

            $dbc->query($createSQL);

            $insertSQL = "INSERT INTO FWE_DEV..{$tableName} (
part
,partDescription
,barcode
) VALUES ";

            for($i=0;$i<$numLabels;$i++){
                if($i!==0){
                    $insertSQL .=",";
                }
                $insertSQL .="(
'".sql_safe($data['part'])."',
'".sql_safe($data['partDesc'])."',
'".sql_safe($data['barcode'])."'
)";
            }

            $dbc->query($insertSQL);


            $foxproReport = new FoxProRunReport(array(
                'mssqlHelperInstance' => $dbc
                ,'template' => 'part_4x75.frx'
                ,'tableName' => 'fwe_dev..['.$tableName.']'
                ,'printerName' => $printer
                ,'cleanupTempTable'=> true
                ,'printerJobName'=> $printJobName

            ));

            if(FALSE == $foxproReport->printReport() ){
                die(json_encode(array('success' => true,'printed' => false, 'error' => $foxproReport->getCurrentErrorDesc())));
            }

            die('{success:true,printed:true}');
        }

        function printLocBinLabel($dbc){

            $printJobName = "Location, Bin Label";
            $LocationBin_Barcode_Prefix = '003';

            require($_SERVER['DOCUMENT_ROOT'] . '/inc/php/classes/ConfigPrinters.php' );
            $printers = ConfigPrinters::getPrinters();

            if( !isset($_POST['printer']) || !array_key_exists($_POST['printer'], $printers) ){
                die('{success:false,errors:{desc:"Missing parameters"}}');
            }
            $printer = $printers[$_POST['printer']]['name'];

            switch($printers[$_POST['printer']]['size']){
                case '4x4':
                    $labelTemplate = "location_bin_4x4";
                    break;

                case '4x75':
                    $labelTemplate = "location_bin_4x75";
                    break;

                case 'mobile':
                    $labelTemplate = "location_bin_4x75";
                    break;

                default:
                    die('{success:false,errors:{desc:"Missing parameters"}}');
            }

            if( !isset($_POST['bin']) || !isset($_POST['location']) ){
                die('{success:false,errors:{desc:"Missing parameters"}}');
            }

            if( isset($_POST['numLabels']) && is_numeric($_POST['numLabels'])){
                $numLabels = $_POST['numLabels'];
            }else{
                $numLabels = 1;
            }
            if( $numLabels > 10 ){
                $numLabels = 10;
            }

            $bin = strtoupper(trim($_POST['bin']));
            if($bin === ''){
                $bin = ' ';
            }
            $location = strtoupper(trim($_POST['location']));

            $sql = "select rtrim(identity_column) id from m2mdata01..bin where fcloc = '".sql_safe($location)."' and fcbin = '".sql_safe($bin)."'";

            if( FALSE === $dbc->query($sql ) ){
                die('{success:false,errors:{desc:"Error getting locations/bins! '.$dbc->getLastMessage().'"}}');
            }
            if( $dbc->num_rows() < 1 ){
                die('{success:true,printed:false,reason:"No bins/locations found!"}');
            }
            $locBin = $dbc->fetch_assoc();

            $labelData = array(
                "bin"=> $bin,
                "loc"=> $location,
                "barcode"=> "*".$LocationBin_Barcode_Prefix . $locBin['id']."*",
                "labelRev"=>InventoryLabelsLegacy::$currentLabelRev
            );

            $date = date("Y_m_d_h_i_s");
            $tableName = "tmp_foxpro_location_bin_label_{$date}_".uniqid();

            $createSQL = "CREATE TABLE FWE_DEV..{$tableName}(
[loc] [varchar](100) NULL,
[bin] [varchar](100) NULL,
[barcode] [varchar](100) NULL
) ON [PRIMARY]
";

            $dbc->query($createSQL);

            $insertSQL = "INSERT INTO FWE_DEV..{$tableName} (
loc
,bin
,barcode
) VALUES ";


            for($i=0;$i<$numLabels;$i++){
                if($i!==0){
                    $insertSQL .=",";
                }
                $insertSQL .="(
'".sql_safe($labelData['loc'])."',
'".sql_safe($labelData['bin'])."',
'".sql_safe($labelData['barcode'])."'
)";
            }

            $dbc->query($insertSQL);


            $foxproReport = new FoxProRunReport(array(
                'mssqlHelperInstance' => $dbc
                ,'template' => $labelTemplate.'.frx'
                ,'tableName' => 'fwe_dev..['.$tableName.']'
                ,'printerName' => $printer
                ,'cleanupTempTable'=> true
                ,'printerJobName'=> $printJobName
            ));

            if(FALSE == $foxproReport->printReport() ){
                die('{success:true,printed:false}');
            }
            die('{success:true,printed:true}');
        }

        static function printLocBinLabelRange($dbc){

            if( isset($_POST['ohOnly']) && $_POST['ohOnly'] == 'true' ){
                $sql = "select distinct '003'+rtrim(bin.identity_column) as barcode, rtrim(bin.identity_column) identity_column,  rtrim(bin.fcloc) fcloc, rtrim(bin.fcbin) fcbin from m2mdata01..inonhd oh, m2mdata01..bin bin where oh.flocation=bin.fcloc and oh.fbinno = bin.fcbin and oh.flocation = '".sql_safe($_POST['location'])."' and oh.fbinno between '".sql_safe($_POST['fromBin'])."' and '".sql_safe($_POST['toBin'])."' order by rtrim(bin.fcbin)"; //and oh.fonhand > 0 // removed AR 2011-08-29
            }else{
                $sql = "select '003'+rtrim(identity_column) as barcode, rtrim(identity_column) identity_column, rtrim(fcloc) fcloc, rtrim(fcbin) fcbin from m2mdata01..bin where fcloc = '".sql_safe($_POST['location'])."' and fcbin between '".sql_safe($_POST['fromBin'])."' and '".sql_safe($_POST['toBin'])."' order by fcbin";
            }
            //$sql = "select distinct '{$this->currentLabelRev}' as labelRev, '003'+rtrim(bin.identity_column) as barcode, rtrim(bin.identity_column) identity_column,  rtrim(bin.fcloc) fcloc, rtrim(bin.fcbin) fcbin from m2mdata01..inonhd oh, m2mdata01..bin bin where oh.flocation=bin.fcloc and oh.fbinno = bin.fcbin and oh.flocation = '".sql_safe($_POST['location'])."' and oh.fbinno between '".sql_safe($_POST['fromBin'])."' and '".sql_safe($_POST['toBin'])."' order by rtrim(bin.fcbin)"; //and oh.fonhand > 0 // removed AR 2011-08-29

            if( FALSE === $dbc->query($sql ) ){
                die('{success:false,errors:{desc:"Error getting locations/bins! '.$dbc->getLastMessage().'"}}');
            }
            if( $dbc->num_rows() < 1 ){
                die('{success:true,printed:false,reason:"<B>There are no parts on hand in the selected location!</B>"}');
            }

            require_once($_SERVER['DOCUMENT_ROOT'] . '/inc/php/classes/ConfigPrinters.php' );
            $printers = ConfigPrinters::getPrinters();

            if( !isset($_POST['printer']) || !isset($printers[$_POST['printer']]) ){
                die('{success:true,printed:false,reason:"No bins/locations found!"}');
            }
            $printer = $printers[$_POST['printer']]['name'];

            if($_POST['labelSize'] == '4'){
                $labelTemplate = "location_bin_4x4";
            }else {
                $labelTemplate = "location_bin_4x75";
            }

            $rawLocationData = $dbc->fetch_all_assoc();
            $labelData = array();
            foreach($rawLocationData as $row){
                $labelData[] = array(
                    'loc' => $row['fcloc'],
                    'bin' => $row['fcbin'],
                    'barcode' => "*".$row['barcode']."*"
                );
            }

            $foxproReport = new FoxProRunReport(array(
                'mssqlHelperInstance' => $dbc
                ,'template' => $labelTemplate.'.frx'
                ,'printerName' => $printer
                ,'cleanupTempTable'=> true
                ,'printerJobName'=> "Location, Bin Label Range"
                ,'data' => $labelData
            ));

            if(FALSE == $foxproReport->printReport() ){
                die('{success:true,printed:false}');
            }
            die('{success:true,printed:true}');
        }

        static function printRequestLabelById($dbc) {

            //error_reporting(0);
            //include($_SERVER['DOCUMENT_ROOT'] . '/router/controllers/inventoryprintlabels.php' );

            if(!isset($_POST['requestId']) ){
                die('{success:false,errors:{desc:"Missing Required Data"}}');
            }

            /*
                        {name:'id',mapping:0},
                        {name:'request_date',mapping:1},
                        {name:'urgency',mapping:2},
                        {name:'stock_type',mapping:3},
                        {name:'part',mapping:4},
                        {name:'part_desc',mapping:5},
                        {name:'quantity',mapping:6},
                        {name:'employee_name',mapping:7},
                        {name:'employee_id',mapping:8},
                        {name:'jo',mapping:9},
                        {name:'comment',mapping:10},
                        {name:'quantity_transfered',mapping:11},
                        {name:'denied',mapping:12},
                        {name:'deny_comment',mapping:13},
                        {name:'part_rev',mapping:14},
                        {name:'floorStock',mapping:15},
                        {name:'backflush',mapping:16},
                        {name:'completed',mapping:17}
            */

            $sql = "select id, request_date,urgency, stock_type,part, part_desc, quantity, freordqty reorderQuantity, deliver_to, rtrim(ffname)+' '+rtrim(fname) employee_name, employee_Id,   jo,  comment, quantity_transfered, denied, deny_comment, part_rev, CASE fbulkissue WHEN 'Y' THEN '1' WHEN 'N' THEN '0' ELSE '0' END as floorStock, CASE fcbackflsh WHEN 'Y' THEN '1' WHEN 'N' THEN '0' ELSE '' END as backflush, completion_Date as completed, rtrim(status) status, flocate1 primaryLocation, fbin1 primaryBin from fwe_dev..inventory_requests left join m2mdata01..inmast on m2mdata01..inmast.fpartno = fwe_dev..inventory_requests.part and m2mdata01..inmast.frev = fwe_dev..inventory_requests.part_rev left join m2mdata01..prempl on employee_Id = fempno where id='".sql_safe($_POST['requestId'])."' order by request_Date";

            if( FALSE === $dbc->query($sql ) ){
                die('{success:false,errors:{desc:"Error retreiving Request Queue<BR><BR>'.$dbc->getLastMessage().'"}}');
            }
            if( $dbc->num_rows() < 1 ){
                die('{success:true,rows:0}');
            }
            $row = $dbc->fetch_assoc();
            $row['reorderQuantity'] = SmartTruncate::truncate($row['reorderQuantity']);

            //Test Printer
            //$printer = 'Zebra  TLP2844 .42 4x4';
            //$printer = 'Zebra  TLP2844 .13 4x4';
            //$printer = 'Zebra  TLP2844 Dyhana 4x4'; //inventory cage
            //$printer = "IT_4x4";//"TN INV 4x4 .205";
            //$printer = 'Zebra  TLP2844 4x.75 (00074d2b133b)'; //receiving

            $printJobName = 'Inventory Request Label';

            if(!isset($row['deliver_to']) ){
                $row['deliver_to'] = '';
            }

            $doNotUseDropZone = "";

            if( trim($row['stock_type']) == "floor" ){
                $typeStr = "";
                if( $row['floorStock'] === '0' ){
                    $doNotUseDropZone = "NOT FLOOR STOCK! DO NOT USE DROP ZONE!";
                    $row['deliver_to'] = 'Primary '.trim($row['primaryLocation']).', '.$row['primaryBin'];
                }

            }else{
                $typeStr = "For Job Order ".$row['jo'];
            }

            if( trim($row['urgency']) == "shiftE" ){
                $row['urgency'] = "End of Shift";
            }else{ //30min
                $row['urgency'] = "30 Minutes";
            }


            $pageData = array(
                "part"=>$row['part'],
                "partDesc"=>$row['part_desc'],
                "quantity"=>$row['quantity'],
                "employee"=>ucwords(strtolower($row['employee_name'])),
                "type"=>$typeStr,
                "urgency"=>$row['urgency'],
                "deliverTo"=>"Deliver To: ".$row['deliver_to'],
                "doNotUseDropZone"=>$doNotUseDropZone,
                "requestDate"=> date('m/d/y g:ia',strtotime($row['request_date'])),
                "barcode"=>'*' . '007'.$row['id'] . '*'
            );

            $date = date("Y_m_d_h_i_s");
            $tableName = "tmp_foxpro_inventoryRequest_{$date}_".uniqid();

            $createSQL = "CREATE TABLE FWE_DEV..{$tableName}(
[part] [varchar](100) NULL,
[partDesc] [varchar](100) NULL,
[quantity] [varchar](100) NULL,
[deliverTo] [varchar](100) NULL,
[employee] [varchar](100) NULL,
[type] [varchar](100) NULL,
[doNotUseDropZone] [varchar](100) NULL,
[requestDate] [varchar](100) NULL,
[urgency] [varchar](100) NULL,
[barcode] [varchar](100) NULL
) ON [PRIMARY]
";

            $dbc->query($createSQL);

            $insertSQL = "INSERT INTO FWE_DEV..{$tableName} (
barcode
,part
,partDesc
,quantity
,deliverTo
,employee
,type
,doNotUseDropZone
,requestDate
,urgency)
VALUES (
'".sql_safe($pageData['barcode'])."',
'".sql_safe($pageData['part'])."',
'".sql_safe($pageData['partDesc'])."',
'".sql_safe($pageData['quantity'])."',
'".sql_safe($pageData['deliverTo'])."',
'".sql_safe($pageData['employee'])."',
'".sql_safe($pageData['type'])."',
'".sql_safe($pageData['doNotUseDropZone'])."',
'".sql_safe($pageData['requestDate'])."',
'".sql_safe($pageData['urgency'])."'
)";

            $dbc->query($insertSQL);


            $foxproReport = new FoxProRunReport(array(
                'mssqlHelperInstance' => $dbc
                ,'template' => 'inventory_request_4x4.frx'
                ,'tableName' => 'fwe_dev..['.$tableName.']'
                ,'printerName' => InventoryLabelsLegacy::getDefaultInventoryPrinter()
                ,'cleanupTempTable'=> true
                ,'printerJobName'=> $tableName
            ));

            if(FALSE == $foxproReport->printReport() ){
                die('{success:true,printed:false}');
            }

            die('{success:true,printed:true}');

        }

        static function printReceivingLabelFromPoItemId($dbc){

            //$_POST['poItemId'] = 2046;
            //$_POST['labelQty'] = 1;
            //$_POST['printer'] = "IT_4x4";

            if( !isset($_POST['poItemId']) || !isset($_POST['labelQty']) ){
                die('missing parameters');
            }

            include($_SERVER['DOCUMENT_ROOT'] . '/mobile/barcodes/allocate_barcode.php' );
            //, im.fdescript, im.identity_column on
            $sql = "select pit.fpono, pit.fpartno, pit.frev, pm.fvendno, pit.FJOKEY from m2mdata01..poitem pit left join m2mdata01..pomast pm on pm.fpono=pit.fpono where pit.identity_column='".sql_safe($_POST['poItemId'])."'";

            if(FALSE === $dbc->query($sql)){
                die('error querying for po item');
            }
            if( $dbc->num_rows() < 1){
                die('po item not found');
            }
            $poRow = $dbc->fetch_assoc();

            $poRow['FJOKEY'] = trim($poRow['FJOKEY']);
            if($poRow['FJOKEY'] !== ''){
                $poRow['fpono'] .= ", Job: ".substr($poRow['FJOKEY'],0,5);
            }

            if( strtoupper(substr($poRow['fpartno'], 0, 3)) === 'SUB' ){
                $poRow['fpartno'] = substr($poRow['fpartno'], 4);
            }

            $sql = "select im.fdescript, im.identity_column, rtrim(im.fmeasure) uom, vendcomments.fvcomment
from
m2mdata01..inmast im
left join (
	select fvcomment, inv.fpartno, inv.fpartrev from m2mdata01..invend inv where inv.fpartno='".sql_safe($poRow['fpartno'])."' and inv.fpartrev='".sql_safe($poRow['frev'])."' and inv.fvendno='".sql_safe($poRow['fvendno'])."'
) as vendcomments on vendcomments.fpartno=im.fpartno and vendcomments.fpartrev=im.frev
where
im.fpartno='".sql_safe($poRow['fpartno'])."' and im.frev='".sql_safe($poRow['frev'])."'";


            if(FALSE === $dbc->query($sql)){
                die('error querying for po item');
            }
            if( $dbc->num_rows() < 1){
                die('po item not found');
            }
            $itemMasterRow = $dbc->fetch_assoc();

            $barcodeType = '5';

            if( FALSE === $newBarcodeCode = allocate_barcode::allocateCode( $dbc, $barcodeType, $_POST['poItemId'], $itemMasterRow['identity_column'], $_POST['labelQty'], '0' ) ){
                die('Unable to allocate barcode!');
            }

            if(isset($_POST['printer'])){
                $printer = $_POST['printer'];
            }else{
                $printer = "Zebra  TLP2844 4x.75 (00074d2b133b)";
            }

            InventoryLabelsLegacy::printReceivingPartLabel($dbc, $newBarcodeCode, $poRow['fpartno'] , $poRow['fpono'], $_POST['recvdate'], $itemMasterRow['fvcomment'] ,$_POST['labelQty'], 1, $itemMasterRow['uom'], $printer );
        }

        static function printReceivingPartLabel($dbc, $barcode, $part, $po, $receiveDate, $inspectionComments, $qty, $copies=1, $uom, $printer=false ){

            $printJobName = "Receiving PO Part Label";
            $Receiving_Barcode_Prefix = '005';
            $data = array(
                'part'=> $part
                ,'barcode'=> '*'.$Receiving_Barcode_Prefix . $barcode.'*'
                ,'po'=> $po
                ,'receivingDate'=> $receiveDate
                ,'qty' => $qty .' '. $uom
                ,'inspectionComments' => $inspectionComments
            );
            $labelDataRows = array();
            for($i=0;$i<$copies;$i++){
                $labelDataRows[] = $data;
            }

            $foxproReport = new FoxProRunReport(array(
                'mssqlHelperInstance' => $dbc
                ,'template' => 'ReceivingPartLabel_4x4.frx'
                ,'printerName' => $printer
                ,'cleanupTempTable'=> true
                ,'printerJobName'=> $printJobName
                ,'data' => $labelDataRows
            ));

            if(FALSE == $foxproReport->printReport() ){
                die('{success:true,printed:false}');
            }
            die('{success:true,printed:true}');
        }

    }