<?php 
	require "display_order.inc";
?>
<!--START BODY -->
		<?php
			
			if ($_POST[type] != "trans")
				echo "<form enctype='multipart/form-data' method=post action='/$_URL[1]/$_URL[2]/'>";

			
			if ($_POST[type] == "") {
				view();
			
			} elseif ($_POST[type] == "add") {
				
				echo "<input type=hidden name=type value='trans'>
				<input type=hidden name=trans value='add'>";

				$row[date] = $_POST[date];
				update_form();
			
			} elseif ($_POST[type] == "change") {
				

				$sql = "SELECT * FROM events WHERE id='$_POST[id]'";
				$result = do_query($sql);
				$row = mysql_fetch_array($result);
				

				echo "<input type=hidden name=type value='trans'>
				<input type=hidden name=trans value='change'>
				<input type=hidden name=id value='$_POST[id]'>";

				update_form();
				
			
			} elseif ($_POST[type] == "delete") {
				
					echo "<input type=hidden name=type value='trans'>
					<input type=hidden name=trans value='delete'>
					<input type=hidden name=id value='$_POST[id]'>";

					delete($_POST[id]);
			
			} elseif ($_POST[type] == "trans") {	
				

				
				if ($_POST[trans] == "add") {


					$sql = "INSERT INTO events (date,end_date,num_days,event,time,location,description,img," .
							 "img_size,display_upcoming, weekly ) VALUES ('$_POST[date]','$end_date','$_POST[num_days]'," .
							 "'$_POST[event]','$_POST[time]','$_POST[location]','$_POST[description]'," .
							 "'$image','$size[3]','$_POST[display_upcoming]','$_POST[weekly]')";	
							 
					do_query($sql); echo mysql_error();
					
					$original_id = mysql_insert_id();
					
					$sql = "UPDATE events SET original_id='$original_id' WHERE id='$original_id'";
					do_query($sql); echo mysql_error();
					
					if($_POST[weekly] == "Yes")
					{ 
						$add_days = 7;
						for ($t=1;$t<=$_POST[num_days];$t++)
						{
							$row_date = explode("-",$_POST[date]);
							$new_date = date("Y-m-d", mktime(0, 0, 0, $row_date[1],$row_date[2] + $add_days,$row_date[0]));
							$sql = "INSERT INTO events (date,end_date,num_days,event,time,location,description,img," .
									 "img_size,display_upcoming, weekly, original_id) VALUES ('$new_date','$end_date','$_POST[num_days]'," .
									 "'$_POST[event]','$_POST[time]','$_POST[location]','$_POST[description]'," .
									 "'$image','$size[3]','$_POST[display_upcoming]','$_POST[weekly]','$original_id')";
							do_query($sql); echo mysql_error();
							
							$add_days += 7;
						}
					}
				
				} elseif ($_POST[trans] == "change") {

					$sql = "DELETE FROM events WHERE original_id='$_POST[id]'";
					do_query($sql);
					
					$sql = "INSERT INTO events (date,end_date,num_days,event,time,location,description,img," .
							 "img_size,display_upcoming, weekly ) VALUES ('$_POST[date]','$end_date','$_POST[num_days]'," .
							 "'$_POST[event]','$_POST[time]','$_POST[location]','$_POST[description]'," .
							 "'$image','$size[3]','$_POST[display_upcoming]','$_POST[weekly]')";	
							 
					do_query($sql);
					
					$original_id = mysql_insert_id();
					
					$sql = "UPDATE events SET original_id='$original_id' WHERE id='$original_id'";
					do_query($sql);
					
					if($_POST[weekly] == "Yes")
					{ 
						$add_days = 7;
						for ($t=1;$t<=$_POST[num_days];$t++)
						{
							$row_date = explode("-",$_POST[date]);
							$new_date = date("Y-m-d", mktime(0, 0, 0, $row_date[1],$row_date[2] + $add_days,$row_date[0]));
							
							$sql = "INSERT INTO events (date,end_date,num_days,event,time,location,description,img," .
									 "img_size,display_upcoming, weekly, original_id) VALUES ('$new_date','$end_date','$_POST[num_days]'," .
									 "'$_POST[event]','$_POST[time]','$_POST[location]','$_POST[description]'," .
									 "'$image','$size[3]','$_POST[display_upcoming]','$_POST[weekly]','$original_id')";
							do_query($sql); echo mysql_error();
							
							$add_days += 7;
						}
					}
				
				} elseif ($_POST[trans] == "delete") {

					$sql = "DELETE FROM events WHERE original_id='$_POST[id]'";
					do_query($sql);
				}
				

				
				echo "<br /><div align=center>The $_POST[trans] operation was successful.</div><br /><br />";
				
				view();
			}

			echo "<br /><div align=center>
				<a href='$_SERVER[PHP_SELF]'>Back to Maintenance Form</a><br />
			</div>";
		?>
			<!--END BODY -->
<?php include("../variables/bottom_section.inc") ?>
<?php include("../variables/admin_menu.inc") ?>
<?php
	function update_form()
	{
		global $row, $_POST, $_GET;
		
		?>
<SCRIPT LANGUAGE='JavaScript'>
<!--
	//This function will format the date and submit the form...
	function prepareData(form) {
		var strMsg;
		var minutes_correct;
		var hours_correct;
		
		current_date = new Date(); //Finding the current date...

		if (form.month.options[form.month.selectedIndex].value == "XXX") strMsg = "You must select a month.";
		else if (form.day.options[form.day.selectedIndex].value == "XXX") strMsg = "You must select a day.";
		else if (form.year.value < current_date.getFullYear() || form.year.value == "") strMsg = "You have entered an invalid year.";
		
		else {
			//Adding the year, month, and day values to the date form element...
			form.date.value = form.year.value + "-" + form.month.options[form.month.selectedIndex].value + "-" + form.day.options[form.day.selectedIndex].value;
			
			minutes_correct = parseInt(form.minutes.value);
			hours_correct = parseInt(form.hour.value) + parseInt(form.am_pm.value);
			
			if(hours_correct == 12) hours_correct = 0;
			if(hours_correct == 24) hours_correct = 12;
						
			if(hours_correct < 10) hours_correct = "0" + hours_correct; 
			if(minutes_correct < 10) minutes_correct = "0" + minutes_correct; 
			
			form.time.value = hours_correct + ":" + minutes_correct + ":00";
			
			//Submit the form...
			form.submit();
			return;
		}

		//If something wasn't entered in correctly, display the strMsg error message and return to the form...
		alert(strMsg + " Please correct and try again.");
		return;
	}
	
//-->
</SCRIPT>
<style>
<!--
TH.event_header {
	text-align: right;
	font-size: 7pt;
}
-->
</style>
<table align='center' width="600" border='0' cellpadding='2' cellspacing='1'>
	<tr>
		<th class="event_header">Date:</th>
		<td><div class=eightb>
			<?php
				//Populating the monthNames, monthOrds, and days arrays...
				$monthNames = array(0=>"January","February","March","April","May","June","July","August","September",
															 "October","November","December");
				$monthOrds= array("01","02","03","04","05","06","07","08","09","10","11","12");
				$days = array(0=>"01","02","03","04","05","06","07","08","09","10","11","12","13","14","15",
												 "16","17","18","19","20","21","22","23","24","25","26","27","28","29","30","31");

				//Retrieving the month, day, & year values from the date that's in the DB...
				$month = substr($row[date],5,2);
				$day = substr($row[date],8,2);
				$year = substr($row[date],0,4);
				if ($year == "") $year = date("Y"); //If the year is blank, set it to the current year...

				echo "<select name=month>
					<option value='XXX'>-----------</option>";
					//Going through all the months, and displaying them in the drop down...
					for ($i=0;$i<count($monthNames);$i++) {
						//If the current month matches the month varible, show it as selected...
						if ($monthOrds[$i] == $month) echo "<option value='$monthOrds[$i]' selected>$monthNames[$i]</option>";
						//Otherwise, just display it...
						else echo "<option value='$monthOrds[$i]'>$monthNames[$i]</option>";
					}
				echo "</select>
				/
				<select name=day>
					<option value='XXX'>--</option>";
					//Going through all the days, and displaying them in the drop down...
					for ($i=0;$i<31;$i++) {
						//If the current day matches the day varible, show it as selected...
						if ($days[$i] == $day) echo "<option value='$days[$i]' selected>$days[$i]</option>";
						//Otherwise, just display it...
						else echo "<option value='$days[$i]'>$days[$i]</option>";
					}
				echo "</select>
				/
				<input type=text name=year size=4 maxlength=4 value='$year'>";
			?>
		</div></td>
	</tr>
	<tr>
		<th class="event_header">Time:</th>
		<td>
			<?php
				$break_time = explode(":",$row[time]);
				if($break_time[0] >= 12) $hour = $break_time[0] - 12; else $hour = $break_time[0];
				$minutes = $break_time[1];
				
				if($hour == "" || $hour == "00") $hour = "12";
				if($minutes == "") $minutes = "00";
			?>
			<input type='text' name='hour' id='hour' size='2' maxlength='2' value="<?php echo $hour; ?>" onBlur="if(document.getElementById('hour').value=='')document.getElementById('hour').value='12'">
			<input type='text' name='minutes' id='minutes' size='2' maxlength='2' value="<?php echo $minutes; ?>" onBlur="if(document.getElementById('minutes').value=='')document.getElementById('minutes').value='00'">
			<select name="am_pm">
			<?php
				$select_ampm = array("am","pm");
				$select_timeadjust = array("0","12");
				for($x=0;$x<count($select_ampm);$x++)
				{
					if($break_time[0] >= 12 && $select_ampm[$x] == "pm")
						echo "<option value='$select_timeadjust[$x]' selected>$select_ampm[$x]</option>\n";
					else
						echo "<option value='$select_timeadjust[$x]'>$select_ampm[$x]</option>\n";
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<th class="event_header">Event:</th>
		<td>
			<input type=text name=event size=50 maxlength=64 value="<?php echo stripslashes($row[event]); ?>">
		</td>
	</tr>
	<tr>
		<th class="event_header">Event Location:</th>
		<td>
			<input type=text name=location size=50 maxlength=96 value="<?php echo stripslashes($row[location]); ?>">
		</td>
	</tr>
	<tr>
		<th class="event_header">Weekly Event?:</th>
		<td>
			No:
			<input type='radio' id='weekly' name='weekly' value='No' <?php if ($row[weekly] != "Yes") echo " checked"; ?> onFocus="document.getElementById('num_weeks').style.visibility = 'hidden';">

			Yes:
			<input type='radio' id='weekly' name='weekly' value='Yes' <?php if ($row[weekly] == "Yes") echo " checked"; ?> onFocus="document.getElementById('num_weeks').style.visibility = 'visible';">
				&nbsp;&nbsp;&nbsp;
			<span  id="num_weeks">
			Number of Weeks:
			<?php
				//If the num_days in the DB isn't blank, display the # of days; otherwise display 1...
				if ($row[num_days] != "")
					echo "<input type=text name=num_days size=3 maxlength=3 value='" . stripslashes($row[num_days]). "'>";
				else echo "<input type=text name=num_days size=3 maxlength=3 value='1'>";
			?>
			</span>
		</td>
	</tr>
	<tr>
		<th class="event_header">Display on Upcoming Events?:</th>
		<td>
			No:
			<input type='radio' name='display_upcoming' value='No' <?php if ($row[display_upcoming] != "Yes") echo " checked"; ?>>
			Yes:
			<input type='radio' name='display_upcoming' value='Yes' <?php if ($row[display_upcoming] == "Yes") echo " checked"; ?>>

		</td>
	</tr>
	<tr>
		<td colspan="2">
			<?php 
				//$row[paragraph] = str_replace("\"","&quot;",$row[paragraph]);
				//echo stripslashes($row[paragraph]); 

				// Automatically calculates the editor base path based on the _samples directory.
				// This is usefull only for these samples. A real application should use something like this:
				// $oFCKeditor->BasePath = '/FCKeditor/' ;	// '/FCKeditor/' is the default value.
				$sBasePath = $_SERVER['PHP_SELF'] ;
				$sBasePath = substr( $sBasePath, 0, strpos( $sBasePath, "_samples" ) ) ;

				$oFCKeditor = new FCKeditor('description') ;
				$oFCKeditor->BasePath	= '/FCKeditor/' ;
				$oFCKeditor->Value = $row[description] ;
				$oFCKeditor->Width  = '100%' ;
				$oFCKeditor->Height = '500' ;

				$oFCKeditor->Create() ;
			?>
		</td>
	</tr>
</table>
<div align=center><br />
	<input type='hidden' name='date' value='$date'>
	<input type='hidden' name='time' value='<?php echo $row[time];?>'>
	<input type='button' name='enter' value='Enter' onClick="prepareData(this.form);">
  <input type=submit name='cancel' value='Cancel' onClick="this.form.type.value=''">
</div></form>
	<?php if ($row[weekly] == "No" || $row[weekly] == "") { ?>
	<SCRIPT LANGUAGE='JavaScript'>
	<!--	
		
		document.getElementById('num_weeks').style.visibility = "hidden";
		//alert(document.getElementById('weekly').value);
	//-->
	</SCRIPT>
	<?php } ?>
<?php 
	}

	function view() 
	{

		global $row, $_POST, $_GET;
				
		echo "</form>";
		//echo "<form method='post' name='add_form' action='$_SERVER[PHP_SELF]'>\n";
		//echo "<div align='center'><input type=submit name='submit' value='Add an Event'></div>\n";
		//echo "<input type=hidden name=type value='add'>\n";
		//echo "</form>";

                
                $color_back = "#8E7B5F"; //lines around boxes
                $empty_back = "#EBE9ED";

                $months = array(1 => "Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
		$days = array(1 => 31,28,31,30,31,30,31,31,30,31,30,31);

		$today = getdate();

                if (!$_GET[month]) $_GET[month] = $today[mon];
                if (!$_GET[year]) $_GET[year] = $today[year];
                 
                //Have to do the following if else condition for when we are on the 31st of a month,
                //  so that it will move to months where there aren't 31 days...
                //If there isn't a day value but there is a move value, set the day to one...
                if (!$_GET[day] && $_GET[move]) $_GET[day] = 1; else if (!$_GET[day]) $_GET[day] = $today[mday];

		//Make a timestamp with the above values, and then get the date of that timestamp...
		$date = mktime(0,0,0,($_GET[month] + $_GET[move]),$_GET[day],$_GET[year]);
		$date = getdate($date);

                //Get the month, year, and day of the date found above...
                $month = $date[mon];
                $year = $date[year];
                $day = $date[mday];

                $actual_date = getdate();
                $todays_date = date("Y-m-d");
		
		//Setting Febuary's End Day to 29 if it's a leap year...
                if ((($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0)) $days[2] = 29;

		echo "<form enctype='multipart/form-data' method='post' name='calendar_form' action='$_SERVER[PHP_SELF]'>\n";
		echo "  <input type='hidden' name='type' value='trans'><input type='hidden' name='trans' value='change'><input type='hidden' name='id'>";
		echo "  <input type='hidden' name='date' value='$todays_date'>";
		echo "<table class='border_color' border='0' cellpadding='1' cellspacing='1' align='center'>
			<tr>
				<td colspan=7 class='admin_cal_calendar_header'>
					<a href='$_SERVER[PHP_SELF]?year=$year&month=$month&move=-1'><-</a>
					<span class='admin_cal_month_title'>$months[$month] '" . substr($year,2,2) . "</span>
					<a href='$_SERVER[PHP_SELF]?year=$year&month=$month&move=1'>-></a>
				</td>
			</tr>
			<tr>
				<th class='admin_cal_day_header'>Sun</th>
				<th class='admin_cal_day_header'>Mon</B></th>
				<th class='admin_cal_day_header'>Tue</B></th>
				<th class='admin_cal_day_header'>Wed</B></th>
				<th class='admin_cal_day_header'>Thu</B></th>
				<th class='admin_cal_day_header'>Fri</B></th>
				<th class='admin_cal_day_header'>Sat</B></th>
			</tr>";

               	//Finding which day of the week the first of the month falls on...
               	$firstDay = date("w",mktime(0,0,0,$month,1,$year));
               	$column = 0;

		echo "<tr>";
                        //Printing the blank squares in the first row that are before the first day of the month...
               	for ($i=0;$i<$firstDay;$i++) {
			echo "<td class='admin_cal_cell_color'>&nbsp;</td>";
                        $column += 1;
               	}

               	//Going through each day of the month...
               	for ($i=1;$i<=$days[$month];$i++) 
               	{
                          //If it's the last day of the row, end the row and start another & reset $column to 0...
                	if ($column == 7) 
                	{
				echo "</tr><tr>";
                            	$column = 0;
			}

			//If the current day is the same as today's date, set it to a different color,
                        if (($i == $actual_date['mday']) && ($month == $actual_date['mon']) &&($year == $actual_date['year'])) 
                        	$class="admin_cal_todays_color";
			else 
				$class="admin_cal_cell_color";

			echo "<td class='$class' valign='top' width='75' height='55'>";

                        //If it's a single digit month, put a 0 in front of it for the sql statement (01)...
                        if (strlen($month) == 1) $sql_month = "0" . $month; else $sql_month = $month;

                        //If it's a single digit day, put a 0 in front of it for the sql statement (01)...
                        if (strlen($i) == 1) $sql_day = "0" . $i; else $sql_day = $i;

			echo "$i ";
			echo "<a href=\"javascript:document.calendar_form.id.value=''; document.calendar_form.date.value='$year-$sql_month-$sql_day'; document.calendar_form.type.value='add';document.calendar_form.submit();\" title='Add Event' style='text-decoration: none;'>+</a> ";
			echo "<br />";
			echo "<div class='admin_cal_display_dates'>\n";

			

			//Selecting all the events from the calendar for this day...
			$sql = "SELECT * FROM events WHERE date='$year-$sql_month-$sql_day' ORDER BY time";
			$result = do_query($sql);
			echo mysql_error();
			

			if (mysql_num_rows($result) > 0) 
			{
				while ($row = mysql_fetch_array($result)) 
				{
					
					$session_time = time_convert($row[time],"1");

					
					echo "<a href=\"javascript:document.calendar_form.id.value=$row[original_id]; document.calendar_form.date.value=''; document.calendar_form.type.value='change';document.calendar_form.submit();\" title='$row[event]'>$session_time</a> ";
					echo "<a href=\"javascript:document.calendar_form.id.value=$row[original_id]; document.calendar_form.date.value=''; document.calendar_form.type.value='delete';document.calendar_form.submit();\"><img src='/variables/images/trash.png' alt='Delete Session' border=0></a><br />"; 
					
				}
			}
			
			echo "</div></form>\n";
			echo "</td>";

                          $column += 1; //Add one to $column... 
                        }

                        //Printing the blank squares in the last row that are after the last day of the month...
			for ($i=$column;$i<7;$i++) echo "<td class='admin_cal_cell_color'>&nbsp;</td>";
			
			echo "</tr>
			</table><br /><br />";
  }

  function delete($id) {
		/*This function selects the home_page entry (and it's info) that matches the id variable from the DB,
    			and displays it to confirm it is the one the user wants to delete...*/
		$sql = "SELECT * FROM events WHERE id='$id'";
    		$result = do_query($sql);
   		$row = mysql_fetch_array($result);

		echo "<br /><div align=center>Are you sure you want to delete the following event?</div><br /><br />
		<table align=center border=0 cellpadding=2 cellspacing=1>
      			<tr>
				<th colspan=2 class=form_header>You have requested to Delete:</th>
      			</tr>
			<tr>
				<th>Date:</th>
				<td>$row[date]</td>
			</tr>
			<tr>
				<th>Event:</th>
				<td>$row[event]</td>
			</tr>
			<tr>
				<th>Event Time:</th>
				<td>$row[time]</td>
			</tr>
			<tr>
				<th>Event Location:</th>
				<td>$row[location]</td>
			</tr>
			<tr>
				<th>Description Of Event:</th>
				<td>$row[description]</td>
			</tr>
    </table>
		<div align=center>
			<input type=hidden name=img_hold value='$row[img]'>
      <br /><input type=submit name='submit' value='Enter'>
      <input type=submit name='cancel' value='Cancel' onClick=\"this.form.type.value=''\">
    </div></form>";
	}
?>