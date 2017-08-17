<?php
include_once("/mfw_www/htdocs/global.php");
/**
 * Created by PhpStorm.
 * User: mfw
 * Date: 17/8/14
 * Time: 下午4:27
 */
/**
 *
select open_udid,
concat('{\"device_type\":\"',device_type,'\",','\"app_code\":\"',if(try_cast(app_code as varchar) is null,'',try_cast(app_code as varchar)),'\"}') as att
from
mobile_event_parquet
where
dt='20170801'
and event_code='page'
limit 5
 */

//json_extract_scalar(attr,'$.mddid') as mddid

//$sql="select open_udid from mobile_event_parquet where dt='20170801' and event_code='page' limit 10";

$sql="select
	open_udid,
	device_type,
	app_code
from
	mobile_event_parquet
where
	dt='20170801'
	and event_code='page'
limit 3";

$result_items = \apps\presto\MFacade_Api::getSelectItems($sql);

//print_r($result_items);

if(count($result_items)>1){
    $id=$result_items[0];
    unset($result_items[0]);
    $cols=array_values($result_items);
    $sql_query="select {$id}, concat('{',";
    foreach ($cols as $col){
//        $sql_query=$sql_query."'\\\"".$col."\\\":\\\"'".",$col,'\\\",',";
        $sql_query=$sql_query."'\"".$col."\":\"'".",$col,'\",',";
    }

    $sql_query=substr($sql_query,0,-3);
    $sql_query=$sql_query."}') as extras";
    $sql_query=$sql_query." from ($sql)";
}else{
    $sql_query=$sql;
}






//print_r($sql_query);
//echo("\r\n");

$gid=51;
$index=stripos($sql_query,"from");
$sub_sql=insertToStr($sql_query,$index-1,",'{$gid}' ");

$sql_insert="insert into ups.mug_test2(did,extras,gid)  {$sub_sql}";



print_r($sql_insert);
echo("\r\n");


$res=KPresto_Api::dosql($sql_insert);
$count=$res[0][0];
print_r($res);
print_r($count);
exit;



print_r($cols);

//$sql2="select {$id},'{'";
//foreach ($cols as $col){
//    $sql2=$sql2."||"."'{$col}'"."||"."':'"."||".$col."||"."','";
//}

$sql2=substr($sql2,0,-3);


$sql2=$sql2."'}'";


print_r($sql2);













function insertToStr($str, $i, $substr){
    //指定插入位置前的字符串
    $startstr="";
    for($j=0; $j<$i; $j++){
        $startstr .= $str[$j];
    }
    //指定插入位置后的字符串
    $laststr="";
    for ($j=$i; $j<strlen($str); $j++){
        $laststr .= $str[$j];
    }
    //将插入位置前，要插入的，插入位置后三个字符串拼接起来
    $str = $startstr . $substr . $laststr;
    return $str;
}