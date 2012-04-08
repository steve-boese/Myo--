<style type="text/css">
<!--
/*********************************************/
/*Calendar display                           */
/*********************************************/

.month_title {
	font-size: 10pt;
}

.border_color {
	background-color: #8E7B5F;
}

.cell_color {
	background-color: #ffffff;
	width: 75px;
	height: 50px;
}

.todays_color {
	background-color: #eeeeee;
}

.display_dates {
	text-align:left;
	font-size: 7pt;
}

.day_header {
	height: 30px;
	text-align: center;
	background-color: #eeeeee;
}

.calendar_header {
	height: 30px;
	text-align: center;
	background-color: #eeeeee;
}

-->
</style>
	<?php

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
      
      for ($i=1;$i<count($_URL);$i++) $url_link += "/".$_URL[i];

		echo "<table class='border_color' border='0' cellpadding='1' cellspacing='1' align='center'>
			<tr>
				<td colspan=7 class='calendar_header'>
					<a href='".$url_link."?year=$year&month=$month&move=-1'><-</a>
					<span class='month_title'>$months[$month] '" . substr($year,2,2) . "</span>
					<a href='".$url_link."?year=$year&month=$month&move=1'>-></a>
				</td>
			</tr>
			<tr>
				<th class='day_header'>Sun</th>
				<th class='day_header'>Mon</B></th>
				<th class='day_header'>Tue</B></th>
				<th class='day_header'>Wed</B></th>
				<th class='day_header'>Thu</B></th>
				<th class='day_header'>Fri</B></th>
				<th class='day_header'>Sat</B></th>
			</tr>";

		//Finding which day of the week the first of the month falls on...
		$firstDay = date("w",mktime(0,0,0,$month,1,$year));
		$column = 0;

		echo "<tr>";
      
      //Printing the blank squares in the first row that are before the first day of the month...
      for ($i=0;$i<$firstDay;$i++) 
      {
			echo "<td class='cell_color'>&nbsp;</td>";
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
				$class="todays_color";
			else 
				$class="cell_color";

			echo "<td class='$class' valign='top'>";

			//If it's a single digit month, put a 0 in front of it for the sql statement (01)...
			if (strlen($month) == 1) $sql_month = "0" . $month; else $sql_month = $month;

			//If it's a single digit day, put a 0 in front of it for the sql statement (01)...
			if (strlen($i) == 1) $sql_day = "0" . $i; else $sql_day = $i;

			echo "$i ";
			echo "<br />";
			echo "<div class='display_dates'>\n";



			//Selecting all the events from the calendar for this day...
			$sql = "SELECT * FROM events WHERE date='$year-$sql_month-$sql_day' ORDER BY time";
			$result = do_query($sql);
			echo mysql_error();


			if (mysql_num_rows($result) > 0) 
			{
				while ($row = mysql_fetch_array($result)) 
				{

					$session_time = time_convert($row[time],"1");


					echo "<a href=\"/plugin/calendar_content_display.php?id=$row[id]\"title='$row[event]'>$row[event] - $session_time</a><br /> ";

				}
			}

			echo "</div>\n";
			echo "</td>";

			$column += 1; //Add one to $column... 
      }

       //Printing the blank squares in the last row that are after the last day of the month...
		for ($i=$column;$i<7;$i++) echo "<td class='cell_color'>&nbsp;</td>";
			
		echo "</tr></table><br /><br />";
?>
