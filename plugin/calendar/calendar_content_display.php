<?php include("../variables/connection.inc") ?>
<?php include("../variables/html_declare.inc") ?>
<?php
$sql = "SELECT * FROM events WHERE id='$_GET[id]'";
$result = do_query($sql);
$header = mysql_fetch_array($result);
echo "<TITLE>$header[event]</TITLE>";
?>

<?php include("../variables/top_section.inc") ?>				
<?php 
	echo "<h1>$header[event]</h1>";
	echo write_date($header[date]) . "- <i>";
	echo time_convert($header[time],"1") . "</i><br />";
	if($header[location] != "") echo $header[location] . "<br />";
	echo "<br /><hr />";
	echo $header[description];
	echo "<br /><hr /><br />";
	echo "<a href=\"#\" onClick=\"history.go(-1)\" style='font-size: 7pt;'>Back to Previous Page</a>\n";
?>
<?php include("../variables/bottom_section.inc") ?>