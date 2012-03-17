<?php
include('../../config2.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/functions.php');

?>

<form action=reprint.php method=post>
Date: <input type=text name=date><br>
Receipt Num: <input type=text name=receipt><br>
<input type=submit name=submit>
<?php
$transNum= isset($_REQUEST['receipt'])?$_REQUEST['receipt']:'';
$date1 = "";
if(isset($_REQUEST['month'])){
    $receipt = $_GET['receipt'];

    if(strlen($_GET['month'])<2){
	   $month = '0'.$_GET['month'];
    }else{
	   $month = $_GET['month'];
    }
    $day = $_GET['day'];
    $year = $_GET['year'];
    $date1 = $year."-".$month."-".$day;
}elseif(isset($_REQUEST['date'])){
   $date = $_REQUEST['date'];
   $tmp = explode("-",$date);
   if (is_array($tmp) && count($tmp)==3){
	$year = strlen($tmp[0]==2)?'20'.$tmp[0]:$tmp[0];
	$month = str_pad($tmp[1],2,'0',STR_PAD_LEFT);
	$day = str_pad($tmp[2],2,'0',STR_PAD_LEFT);
	$date1 = $year."-".$month."-".$day;
   }
   else {
	$tmp = explode("/",$date);
	if (is_array($tmp) && count($tmp)==3){
		$year = strlen($tmp[2]==2)?'20'.$tmp[2]:$tmp[2];
		$month = str_pad($tmp[0],2,'0',STR_PAD_LEFT);
		$day = str_pad($tmp[1],2,'0',STR_PAD_LEFT);
		$date1 = $year."-".$month."-".$day;
	}
	else $date1 = $date;
   }
}

function receiptHeader($date,$trans){
   global $dbc,$FANNIE_ARCHIVE_DB, $FANNIE_TRANS_DB, $FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DBMS, $FANNIE_ARCHIVE_REMOTE, $FANNIE_ARCHIVE_METHOD;
   $dbconn = ($FANNIE_ARCHIVE_DBMS=='MSSQL')?'.dbo.':'.';
   if (!$FANNIE_ARCHIVE_REMOTE)
	   $dbconn = ($FANNIE_SERVER_DBMS=='MSSQL')?'.dbo.':'.';

   $totime = strtotime($date);
   $month = date('m',$totime);
   $year = date('Y',$totime);
   $day = date('j',$totime);
   $transact = explode('-',$trans);
   $emp_no = $transact[0];
   $trans_no = $transact[2];
   $reg_no = $transact[1];
   $head = $FANNIE_ARCHIVE_DB.$dbconn.'rp_receipt_header_'.$year.$month;
   $rp= $FANNIE_ARCHIVE_DB.$dbconn.'rp_dt_receipt_'.$year.$month;
   if ($FANNIE_ARCHIVE_METHOD == 'partitions'){
	$head = $FANNIE_ARCHIVE_DB.$dbconn.'rp_receipt_header_big';
	$rp = $FANNIE_ARCHIVE_DB.$dbconn.'rp_dt_receipt_big';
   }

   $queryHead = "SELECT * FROM $head WHERE "
		.$dbc->date_equals('dateTimeStamp',"$year-$month-$day")
               ." and trans_num = '$trans' ";
   
   $query1 = "SELECT description,comment,total,Status,
		datetime,register_no,emp_no,trans_no,memberID FROM $rp WHERE "
		.$dbc->date_equals('datetime',"$year-$month-$day")
           ." and trans_num = '$trans'"
           ." ORDER BY trans_id";
   receipt_to_table($query1,$queryHead,0,'FFFFFF');
}

$border = 0;
//$color = #000000

if ($_REQUEST['receipt'])
	receiptHeader($date1,$transNum);

?>
