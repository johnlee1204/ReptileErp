<?php


class LegacyArjMssqlCrudHelper {

	//function __construct(){
	
	//}
	
	static function recordBrowser(&$recordSpec, &$browseSpec, &$data ){
	
			//check if filters are present and count them
		if( isset($data['filt']) && is_array($data['filt']) ){
			//$filtCount = count($data['filt']);
			$filtCount = 1;
		}else{
			$filtCount = 0;
		}
	
		//if there are no filters, generate a filter that doesn't filter (1=1)
		if($filtCount === 0){
			$filtStr = " 1=1 ";
		}else{
	
			//if there are filters, then generate a filter string
	
			//echo print_r($filt);
			$filtCount = 1;
			$filtStr = "";
			while($curFilt = current($data['filt'])) {
				if(	$filtCount > 1 ){
					$filtStr .= " AND ";		
				}
			
				//check if the filter sent is valid, if not, stop everything and quit
				if(!array_key_exists(key($data['filt']),$recordSpec)){
					die("{success: false,errors:{desc: \"missing parameters\"}}");
				}
				$thisField = $recordSpec[key($data['filt'])];
		
				//begin generating the filter string by getting table and column name
				$filtStr .= $thisField['sqlTable'].".".$thisField['sqlCol'];
	
				//!! MUST PROTECT AGAINST SQL INJECTION HERE
				// cleanse {$curFilt} for any malicious sql input
				$curFilt = sql_safe(stripslashes(trim($curFilt)));
			
				//Check if there is already a wildcard in filter sent
				$pos = strpos($curFilt,'%');

				//if there is no wildcard, check if you need one, append if necessary. If there IS a wildcard, then add a LIKE to the filt str
				if ($pos === false){
					if($thisField['searchO']['appendWild']){
						$curFilt .= '%';
						$filtStr .= ' LIKE ';
					}else{
						$filtStr .= '=';
					}
				}else{
					$filtStr .= ' LIKE ';
				}		
				
				//check to see if search should be padded
				if($thisField['searchO']['pad']){
					$curFilt = str_pad($curFilt,$thisField['searchO']['padO']['len'],$thisField['searchO']['padO']['char'],$thisField['searchO']['padO']['type']);
				}
				
				//finall, add the filter string data		
				$filtStr .= "'".$curFilt."'";
	
				$filtCount++;
				next($data['filt']);
			}
		}
		// EO-Browser Filters
		//-------------------------

		//begin determining the page's record span
		if( isset($data['limit']) ) {
			if(trim($data['limit']) === '' || !is_numeric($data['limit']))
			$data['limit'] = 25;
		}else{
			$data['limit'] = 25;
		}

		if(isset($data['start'])){
			$start = trim($data['start']);
			if($start === '' || !is_numeric($data['limit'] ) )
				$start = 0;
		}else{
			$start = 0;
		}

		$top = $start + $data['limit'];
	
		///////////////////////////
		// Sorting the SQL Query
		
		if(isset($data['sort'])){
			$sort = trim($data['sort']);

			if($sort === '' || !array_key_exists($sort,$browseSpec['browFields'])){ //!! new in_arr
				$sort = $browseSpec['defaultSort']['f'];
				//DEBUG
				//echo "default sort, empty or not found";
			}
		}else{
			$sort = $browseSpec['defaultSort']['f'];
			//DEBUG
			//echo "default sort, sort not set";
		}
	
		if(isset($data['dir'])){
			$sort_dir = trim($data['dir']);
			if($sort_dir === '' || ($sort_dir !== 'DESC' && $sort_dir !== 'ASC'))
				$sort_dir = $browseSpec['defaultSort']['dir'];
		}else{
			$sort_dir = $browseSpec['defaultSort']['dir'];
		}
		
		if($sort_dir == 'DESC'){
			$op_sort_dir = 'ASC';
		}else{
			$op_sort_dir = 'DESC';
		}
	
		$op_sort = $sort." ".$op_sort_dir;
		$sortStr = $sort." ".$sort_dir;


		$sql1 = '
select 
count('.$browseSpec['browCount']['ta'].'.'.$browseSpec['browCount']['f'].') as t
from
 '.$browseSpec['browCount']['t'].' '.$browseSpec['browCount']['ta'].'
where '.$filtStr.'
';

		//die($sql1);

		//reset browse array, just in case
		//reset($browseSpec['browFields']);

		$colCount = 1;
		$colList = "";
		$colAliasList = "";

		//iterate browse field array, build list of fields
		while($thisCol = current($browseSpec['browFields'] ) ) {
			if(	$colCount > 1 ){
				$colList .= ",\r\n";
				$colAliasList .= ',';
			}
	
			if($thisCol['fop']){
				$colList .= $thisCol['fop_pre'].$thisCol['f'].$thisCol['fop_post'].' '.key($browseSpec['browFields']);
			}else{
				$colList .= $thisCol['f'].' '.key($browseSpec['browFields']);
			}	
			$colAliasList .= key($browseSpec['browFields']);

			$colCount++;
	
			next($browseSpec['browFields']);
		}

		//debug
		//echo $colAliasList;
		//echo "\r\n\r\n";
		//echo $colList;
		//die();

		$sql2 = '
select
'.$colList.'
from '.
$browseSpec['browCount']['t'].' as '. $browseSpec['browCount']['ta'].'
where '.$browseSpec['browCount']['ta'].'.'.$browseSpec['browCount']['f'].' in (
	select top '.$data['limit'].'
	i
	from (
		select top '.$top.'
		i,'.$sort.'
		from (
			select top '.$top.' '.
			$browseSpec['browCount']['f'].' i,
			'.$browseSpec['browFields'][$sort]['f'].' '.$sort.'
			from '.
			$browseSpec['browCount']['t'].' as '. $browseSpec['browCount']['ta'].'
			where ('.$filtStr.')
			order by '.$sortStr.'
		) as T1 order by '.$op_sort.'
	) as T2
) order by '.$sortStr.'
';

		//DEBUG
		//die($sql2);

		return array($sql1,$sql2);
	}
	
	static function buildDeleteQuery(&$recordSpec, &$data){
		
		$queryColumns = array();
		$filterColumns = array();
		foreach( $recordSpec['fields'] as $columnAlias => $columnSpec ){
			
			//check if any inline mysql processing is required, such as a trim(), or any type of date formatting
				//allows a field prefix and suffix to be specified for any mysql server-side processing to be done on the column
//			if($columnSpec['fop'] !== FALSE ){
//				$colStr = $columnSpec['fop_pre'] . $recordSpec['database'] . '..' . $recordSpec['table'] . '.' . $columnSpec['column'] . $columnSpec['fop_post'];
//			}else{
//				$colStr = $recordSpec['database'] . '..' . $recordSpec['table'] . '.' . $columnSpec['column'];
//			}
			
			if( isset($data[$columnAlias]) ) {
				array_push($filterColumns, '['.$recordSpec['database'] . ']..[' . $recordSpec['table'] . '].[' .$recordSpec['fields'][$columnAlias]['column'].']=\''.$data[$columnAlias].'\'');
			}
			
			// 
			//array_push($queryColumns, $colStr.' as '.$columnAlias );
		}

		$outStr = 'DELETE FROM ['.$recordSpec['database'].']..['.$recordSpec['table'].']';
	
		if(count($filterColumns ) > 0 ){
			$outStr .= ' WHERE '.implode(' AND ',$filterColumns);
		}
		
		return $outStr;		
	}
	
	static function buildCreateQuery(&$recordSpec, &$data){
	
		$columnArray = array(); //list of tables to be used in this query
		$valueArray = array(); //list of tables to be used in this query
		$colsProcessed = 0;
		foreach($recordSpec['fields'] as $field=>$fieldSpec){
		
			//check to see if the field exists in the passed data array
			if(!isset($data[$field] ) ) {
				continue;
			}
			
			array_push($columnArray,$fieldSpec['column'] );
			array_push($valueArray,'\''.sql_safe($data[$field] ).'\'' );
			$colsProcessed++;
		}
		
		if($colsProcessed < 1){
			return FALSE;
		}
		return 'INSERT INTO ['.$recordSpec['database'].']..['.$recordSpec['table'].'] ('.implode(',', $columnArray).') VALUES('.implode(',', $valueArray).')'; 
	}

	static function buildUpdateQuery(&$recordSpec, &$data ){
		
		if(!isset($data[$recordSpec['primaryKey']] ) ){
			return FALSE;
		}
		
		$columnArray = array();
		$colsProcessed = 0;
		foreach($recordSpec['fields'] as $fieldAlias=>$fieldSpec){
			//check to see if the field exists in the past
			if(!isset($data[$fieldAlias] ) || $recordSpec['primaryKey'] === $fieldAlias ) {
				continue;
			}
			//var_dump($fieldSped);
			if($data[$fieldAlias] == ''){
				array_push($columnArray, '['.$fieldSpec['column'].']=NULL' );
			}else{
				array_push($columnArray, '['.$fieldSpec['column'].']=\''.sql_safe($data[$fieldAlias]).'\'' );
			}
			
			$colsProcessed++;
		}
		
		if($colsProcessed < 1){
			return FALSE;
		}
		return 'UPDATE ['.$recordSpec['database'].']..['.$recordSpec['table'].'] SET '.implode(',', $columnArray).' WHERE ['.$recordSpec['database'].']..['.$recordSpec['table'].'].['.$recordSpec['fields'][$recordSpec['primaryKey']]['column'].']=\''.$data[$recordSpec['primaryKey']].'\'';
		
	}
	
	static function buildReadQuery(&$recordSpec, &$data = array() ){
		
		$queryColumns = array();
		$filterColumns = array();
		foreach( $recordSpec['fields'] as $columnAlias => $columnSpec ){
			
			if( $columnSpecArray = is_array($columnSpec) ){
				$columnName = $columnSpec['column'];
			}else{
				$columnAlias = $columnSpec;
				$columnName = $columnSpec;
			}
			//check if any inline mysql processing is required, such as a trim(), or any type of date formatting
				//allows a field prefix and suffix to be specified for any mysql server-side processing to be done on the column
			
			if( isset($data[$columnAlias]) ) {
				array_push($filterColumns, "[{$recordSpec['database']}]..[{$recordSpec['table']}].[{$columnName}] = '{$data[$columnAlias]}'");
			}
			
			if($columnSpecArray && $columnSpec['fop'] !== FALSE ){
				$colStr = "{$columnSpec['fop_pre']}[{$recordSpec['database']}]..[{$recordSpec['table']}].[{$columnName}]{$columnSpec['fop_post']}";
			}else{
				$colStr = "[{$recordSpec['database']}]..[{$recordSpec['table']}].[{$columnName}]";
			}
			
			array_push($queryColumns, "{$colStr} as [{$columnAlias}]");
		}

		$outStr = 'SELECT '.implode(',',$queryColumns)." FROM [{$recordSpec['database']}]..[{$recordSpec['table']}]";
	
		if(count($filterColumns ) > 0 ){
			$outStr .= ' WHERE '.implode(' AND ',$filterColumns);
		}
		
		return $outStr;		
	}

}


?>