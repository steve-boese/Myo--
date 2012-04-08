<?php
  global $_URL;

  $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/calendar.css";

  $table = 'event';

  $parms = array();
  for ($i=2; $i<count($_URL); $i++) {
    if ($_URL[$i] <> '') {
      $parm = explode(':',$_URL[$i]);
      $parms[$parm[0]] = $parm[1];
    }
  }
  items_list($table,$_URL[1],$parms);


  function items_list($table,$node,$parms) {

    $months = do_query("
      select distinct
        date_format(start,'%Y-%m') as yr_month,
        date_format(start,'%M, %Y') as month_display
      from
        event
      where
        deleted = '".ZERO_DATE."' and
        date_format(start,'%Y-%m') >= date_format(now(),'%Y-%m')
      order by
        start");

    $dd_code = "
              <option value='%s'>%s</option>";
    $mth_opts = sprintf($dd_code,'0','Select One');
    while ($m = $months->fetch_object())
      $mth_opts .= sprintf($dd_code,$m->yr_month,$m->month_display);
      
?>

          <div id="cal_page">
            <label for="search_mth">Search by Month:</label>
            <select id="search_mth" name="search_mth" style="width:200px;"
                      onchange="window.location='<?=SITE_HTTP."/$node/month:";?>' + this.value;"><?=$mth_opts;?>

            </select>

<?

    if (isset($parms['month'])) {
      $where = "
        (substr(start,1,7) = '{$parms['month']}' or
         substr(end,1,7)   = '{$parms['month']}') and";
      $GLOBALS['pageInfo']['title'] = date('F Y',strtotime($parms['month'].'-01'))." ".$GLOBALS['pageInfo']['title'];
    } else
      $where = "
        (start > now() - interval 1 day or
        (end <> '".ZERO_DATE."' and end  > interval -12 hour + now())) and";

    $events = do_query("
      select
        *
      from
        event
      where $where
        deleted = '".ZERO_DATE."'
      order by
        start,
        end,
        event");
        
    if (in_array($_SESSION[SITE_PORT]['user_type'],array('1','2'))
       || in_array(19,explode(',',$_SESSION[SITE_PORT]['modules'])))
      list($editable,$onclick) = array(" editable","
                 onclick=\"location.href='".SITE_HTTP."/admin/event/edit/%s';\"");
    else
      $onclick = "id='event%s'";


    $c_month_header = "
            <h2>%s</h2>";
    $c_event_wrap = "
            <div class='one_event$editable' $onclick>%s
            </div>";
    $c_date = "
              <h3>%s</h3>";
    $c_event = "
              <h4>%s</h4>";
    $c_location = "
              <h4 class='loc'>Location: %s</h4>";
    $c_description = "
              <p>%s
              </p>";

    while ($e = $events->fetch_object()) {
      if (substr($e->start,0,7) <> $curr_mo) {
        printf($c_month_header,date('F Y',strtotime($e->start)));
        $curr_mo = substr($e->start,0,7);
      }
      list ($start,$end,$range) = date_range($e);
      $event = sprintf($c_date,$range);
      if ($e->event <> '')
        $event .= sprintf($c_event,$e->event);
      if ($e->location <> '')
        $event .= sprintf($c_location,nl2br($e->location));
      $links = array();
      if ($e->page_name <> '') {
        $pg_title = do_query("select title from page
                               where file_name = '{$e->page_name}'
                                 and deleted = '".ZERO_DATE."'");
        if ($pg_title->num_rows > 0) {
          $p_title = $pg_title->fetch_object();
          $link_title = $p_title->title;
          $links[] = sprintf("<a href='/%s'>%s</a>",$e->page_name,$link_title);
        }
      }
      if (in_array(substr($e->url,0,7),array('http://','https:/')))
        $links[] = sprintf("<a href='%s'>%s</a>",$e->url,str_replace('http://','',$e->url));
      $text = array();
      if (strlen($e->description) > 0)
        $text[] = nl2br($e->description);
      if (count($links) > 0)
        $text[] = "More information at: ".implode(' and ',$links).'.';
      if (count($text) > 0)
        $event .= sprintf($c_description,implode('<br />',$text));
      printf($c_event_wrap,$e->id,$event);
    }

    echo "
          </div>";
  }
  
  function date_range($item) {
    $start = dbdate_show_long($item->start);
    $end   = dbdate_show_long($item->end);
    if ($end == '' || $start == $end)
      $range = $start;
    else {
      list($dStart,$dEnd) = array(explode(' ',$start),explode(' ',$end));
      list($tStart,$tEnd) = array(array_pop($dStart),array_pop($dEnd));
      if ($dStart == $dEnd)
        $range = "$start &ndash; $tEnd";
      else
        $range = "$start &ndash; $end";
    }
    return array($start,$end,$range);
  }


?>