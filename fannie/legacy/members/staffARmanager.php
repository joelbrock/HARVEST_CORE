<?php
include('../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db2.php');
$sql->query("use is4c_trans");

/* delete is easy
 * just delete from staffID and staffAR
 */
if (isset($_POST['remove'])){
	$cardno = $_POST['cardno'];
	$delQ = "delete from staffID where cardno=$cardno";
	$delR = $sql->query($delQ);
	$delQ = "delete from staffAR where cardNo=$cardno";
	$delQ = $sql->query($delQ);
	echo "Member #$cardno removed from staff AR<p />";
}
/* add an employee to staffAR
 * this requires an ADP ID, which I attempt to find using
 * first and last names, otherwise the user is prompted to
 * enter the ADP ID 
 * redeux: just lastname. employees on nexus tends to have full[er] names
 * and middle initials
 */
if (isset($_POST['add'])){
	$cardno = $_POST['cardno'];
	
	$namesQ = "select FirstName,LastName from is4c_op.custdata where CardNo=$cardno and personNum=1";
	$namesR = $sql->query($namesQ);
	$namesW = $sql->fetch_array($namesR);
	$fname = $namesW[0];
	$lname = $namesW[1];
	
	echo "Enter the employee's ADP ID#<br />";
	echo "<form method=post action=staffARmanager.php>";
	echo "<input type=text name=adpID value=100 /> ";
	echo "<input type=submit value=Submit />";
	echo "<input type=hidden name=cardno value=$cardno />";
	echo "</form>";
	return; // not done adding yet
}
/* adp id wasn't found, so a form of
 * some kind was submitted to fill it in
 */
 if (isset($_POST['adpID'])){
	$cardno = $_POST['cardno'];
	$adpID = $_POST['adpID'];
	// the user provided an adp id
	if ($adpID != 'None of these'){
		$insQ = "insert into staffID values ($cardno,$adpID,1)";
		$insR = $sql->query($insQ);
		balance($cardno);
		echo "Member #$cardno added to staff AR";
	}
	// the user didn't like the possible choices presented, give
	// manual entry form
	else {
		echo "Enter the employee's ADP ID#<br />";
		echo "<form method=post action=staffARmanager.php>";
		echo "<input type=text name=adpID value=100 /> ";
		echo "<input type=submit value=Submit />";
		echo "<input type=hidden name=cardno value=$cardno />";
		echo "</form>";
		return; // not done adding yet
	}
}

// add the correct balance for the cardno to staffAR
function balance($cardno){
	global $sql;
	$balanceQ = "INSERT INTO staffAR (cardNo, lastName, firstName, adjust)
                 	SELECT
                 	CardNo,
                 	LastName,
                 	FirstName,
                 	Balance as Ending_Balance
                 	from is4c_op.custdata where CardNo=$cardno and personNum=1";
	$balanceR = $sql->query($balanceQ);
}

// main insert / delete form follows
?>
<form action=staffARmanager.php method=post>
<b>Add employee</b>:<br />
Member number: <input type=text name=cardno /> 
<input type=hidden name=add value=add />
<input type=submit value=Add />
</form>
<hr />
<form action=staffARmanager.php method=post>
<b>Remove employee</b>:<br />
Member number: <input type=text name=cardno /> 
<input type=hidden name=remove value=remove />
<input type=submit value=Remove />
</form>
