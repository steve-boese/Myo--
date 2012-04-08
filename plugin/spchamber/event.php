<?php
  require(ABSPATH . 'plugin/spchamber/functions.php');

  global $_URL;

  $table = 'event';

  if (count($_POST) > 0) {
    db_update($table,$_URL[3],$_URL[4]);
    if (isset($GLOBALS['next_page']))
      header("Location: {$GLOBALS['next_page']}");
    else
      header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && !in_array($_URL[3],array('','list'))) {
    item_form($table,$_URL[2],$_URL[3],$_URL[4]);

  } else {
    $parms = array();
    for ($i=4; $i<count($_URL); $i++) {
      if ($_URL[$i] <> '') {
        $parm = explode(':',$_URL[$i]);
        $parms[$parm[0]] = $parm[1];
      }
    }
    items_list($table,$_URL[2],$parms);
  }

  function items_list($table,$node,$parms) {

    if (!isset($parms['cat']))
      $parms['cat'] = (isset($parms['year'])) ? 'year' : 'pending';
    if (!isset($parms['order']))
      $parms['order'] = ($parms['cat'] == 'pending') ? 'forward' : 'backward';

    list($title,$items) = retrieve_item_list($table,$parms);

    $years = retrieve_item_years($table);
    $year_code = "
            <a class='btn' href='/admin/%s/list/year:%s' title='Events during %s'>%s</a>";
    while ($yr = $years->fetch_object())
      $yrs .= sprintf($year_code,$node,$yr->year,$yr->year,$yr->year);
    $years->close();
    $currcat = (isset($parms['year'])) ? "year:{$parms['year']}" : "cat:{$parms['cat']}";
    $switch_order = ($parms['order'] == 'backward') ? 'forward' : 'backward';

?>
      <h1><?=$title;?></h1>
      <table class='edit_list events'>
        <tr>
          <td colspan='5'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add new event</a>
            &nbsp; (Total: <?=$items->num_rows;?>)&nbsp;&nbsp;&nbsp;&nbsp;
            <a class='btn' href='/admin/<?=$node;?>/list/cat:recent' title='Last 3 months'>Recent</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:pending' title='Up-coming'>Upcoming</a>
            <a class='btn' href='/admin/<?=$node;?>/list/cat:all/order:backward' title='All Events'>All</a><?=$yrs;?>
          </td>
        </tr>
        <tr>
          <th style='width:125px;'>
            <a href='/admin/<?=$node;?>/list/<?=$currcat;?>/order:<?=$switch_order;?>/' title='Reverse Order'>Start</a>
          </th>
          <th style='width:125px;'>
            <a href='/admin/<?=$node;?>/list/<?=$currcat;?>/order:<?=$switch_order;?>/' title='Reverse Order'>End</a>
          </th>
          <th style='width:200px;'>
            <a href='/admin/<?=$node;?>/list/<?=$currcat;?>/order:event/' title='Order by Event Title'>Event</a>
          </th>
          <th style='width:200px;'>
            Location
          </th>
          <th>&nbsp;</th>
        </tr>
<?

    while ($item = $items->fetch_array()) {

      $start = dbdate_show($item['start'],true);
      $end   = dbdate_show($item['end'],true);
      list($row_class,$btn_prefix) = array('','');
      if (is_dbdate($item['deleted']))
        list($row_class,$btn_prefix) = array("deleted","Un-");
      if (strlen($row_class) > 0)
        $row_class = "class='$row_class'";

?>
        <tr <?=$row_class;?>>
          <td><?=$start;?></td>
          <td><?=$end;?></td>
          <td><?=$item['event'];?></td>
          <td><?=nl2br($item['location']);?></td>
          <td class='buttons'><a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a><a class='btn' href='/admin/<?=$node;?>/<?=$btn_prefix;?>delete/<?=$item['id'];?>/'><?=$btn_prefix;?>Delete</a></td>
        </tr>
<?
    }
    $items->close();
?>
      </table>

<?}

  function item_form($table,$node,$action,$id) {
    set_form_referer();

    $actions = array(
       "add"       => "Add ".ucwords($node),
       "edit"      => "Update ".ucwords($node),
       "delete"    => "Delete This ".ucwords($node),
       "Un-delete" => "Un-delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($table,$id);
      
    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

    $event_pgs = do_query("
      select
        file_name,
        menu_name
      from
        page
      where
        sub_id in
         (select id
            from page
           where menu_name in ('event','events')
             and status = 'active'
             and deleted = '".ZERO_DATE."'
             and sub_id = 0) and
        deleted = '".ZERO_DATE."' and
        status in ('active','draft')
      order by
        display_order");
    $opt_code = "
              <option value='%s' %s>%s</option>";
    $page_options = sprintf($opt_code,'','','');
    while ($event_pg = $event_pgs->fetch_object()) {
      $slctd = "";
      if ($event_pg->file_name == $item['page_name']) {
        $found = true;
        $slctd = "selected='selected'";
      }
      $page_options .= sprintf($opt_code,$event_pg->file_name,$slctd,$event_pg->menu_name);
    }

    form_v($item);
    $tbi = 1;
    
    $cancel_to = (isset($_SESSION['form_referer'])) ?
                 $_SESSION['form_referer']
               : SITE_HTTP."/admin/$node";

?>

      <form id='update_event' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> <?=ucwords($node);?></h2>
        <div class='field_input'>
          <div><?=input_field('event',$tbi++,'Event name:',null,$item['event'],'300px');?>
          </div>
          <div><?=input_field('start',$tbi++,'Start date/time:',null,dbdate_show($item['start']),'146px');?>
          </div>
          <div><?=input_field('end',$tbi++,'End date/time:',null,dbdate_show($item['end']),'146px');?>
          </div>
          <div><?=input_field('location',$tbi++,'Location:','textarea',$item['location'],'300px','height:4.2em');?>
          </div>
          <div><?=input_field('description',$tbi++,'Event description:','textarea',$item['description'],'300px','height:10.8em');?>
          </div>
          <div>
            <label for="page_name">Internal page:</label>
            <select name="page_name" tabindex="<?=$tbi++;?>" id="page_name" style="width:300px;"><?=$page_options;?>
            
            </select>
          </div>
          <div><?=input_field('url',$tbi++,'External URL:',null,$item['url'],'300px');?>
          </div>
          <div><?=input_field('notes',$tbi++,'Internal notes:','textarea',$item['notes'],'300px','height:7.2em');?>
          </div>
        </div>
        <div class='buttons'>
          <?=input_field('enter',$tbi++,null,'submit',$actions[$action]);?>
          <?=input_field('reset',$tbi++,null,'reset','Cancel',null,null,null,
              "onclick='window.location = \"$cancel_to\"'");?>
        </div>
        <ul class='date_block'><?=date_block($item);?>

        </ul>
      </form>

<?
}

  function retrieve_item_list($table,$parms) {

    $sort = array(
      'forward'  => 'order by start,event',
      'backward' => 'order by start desc,event',
      'event'    => 'order by event,start');
      
    $sortfld = $sort[$parms['order']] or $sort['forward'];
    
    $catg = array(
      'pending'  => array('query' => 1,
                          'where' => "(start > now() - interval 1 day or (end <> '".ZERO_DATE."' and end  > interval -12 hour + now())) and",
                          'order' => $sortfld,
                          'title' => 'Upcoming Events'),
      'recent'   => array('query' => 1,
                          'where' => 'start between now() - interval 3 month and now() + interval 1 day and',
                          'order' => $sortfld,
                          'title' => 'Recent Events'),
      'year'     => array('query' => 1,
                          'where' => "year(start) = '{$parms['year']}' and",
                          'order' => $sortfld,
                          'title' => "{$parms['year']} Events"),
      'all'      => array('query' => 1,
                          'order' => $sortfld,
                          'title' => "All Events"));

    $category = $catg[$parms['cat']] or $catg['all'];

    switch ($category['query']) {
      case 1:
        return array($category['title'],do_query("
          select
            *
          from
            $table
          where
            {$category['where']}
            deleted = 0
          {$category['order']}"));
    }
  }
  
  function retrieve_item_years($table) {
    return do_query("
      select distinct
        year(start) as year
      from
        $table
      where
        year(start) <> 0 and
        deleted = 0
      order by
        year(start) desc");
  }

  function retrieve_item($table,$id) {
    $item = do_query("
      select
        t.*,
        cre.username as created_by_name,
        upd.username as updated_by_name,
        del.username as deleted_by_name
      from
        $table t
      left join staff cre on t.created_by = cre.id
      left join staff upd on t.updated_by = upd.id
      left join staff del on t.deleted_by = del.id
      where
        t.id='$id'");
    return $item->fetch_array();
  }

  function db_update($table,$action,$id,$id2 = 0) {
    if (isset($_POST['cancel']))
      return;
    switch ($action) {
      case "add":
        insert_item($table);
        break 1;
      case "edit":
        update_item($table,$id);
        break 1;
      case "delete":
        delete_item($table,$id);
        update_site_content("calendar","content","calendar");
        break 1;
      case "Un-delete":
        delete_item($table,$id);
        update_site_content("calendar","content","calendar");
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {
    $dateflds = array('start','end');
    foreach ($dateflds as $date) {
      if ($_POST[$date] <> '')
        $_POST[$date] = dbdate($_POST[$date]);
    }
    add_slashes($_POST);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset')) && strlen($value) > 0) {
        $updflds[] = $fld;
        $updvals[] = "'$value'";
      }
    }
    if (!is_array($updflds)) return;
    $updflds = array_merge($updflds,array("created","created_by"));
    $updvals = array_merge($updvals,array("now()","'{$_SESSION[SITE_PORT]['user_id']}'"));
    do_query("
      insert into
        $table
         (".implode(',',$updflds).")
        values
         (".implode(',',$updvals).")");
    $new_id = $GLOBALS['db']->insert_id;
    update_site_content("calendar","content","calendar");
  }

  function update_item($table,$id) {
    $row = do_query("
      select
        *
      from
        $table
      where
        id = '$id'");
    $item = $row->fetch_array();
    $dateflds = array('start','end');
    foreach ($dateflds as $date)
      $_POST[$date] = dbdate($_POST[$date]);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset'))) {
        if ($_POST[$fld] <> $item[$fld]) {
          if (strlen(trim($_POST[$fld])) == 0)
            $newval = "null";
          else
            $newval = "'".add_slashes($_POST[$fld])."'";
          $updts[] = "$fld = $newval";
        }
      }
    }
    if (isset($updts) && is_array($updts)) {
      $updts[] = "updated = now()";
      $updts[] = "updated_by = '{$_SESSION[SITE_PORT]['user_id']}'";
      do_query("
         update
           $table
         set
            ".implode(',',$updts)."
         where
           id='$id'");
    }
    retrieve_form_referer();
    update_site_content("calendar","content","calendar");
  }

?>