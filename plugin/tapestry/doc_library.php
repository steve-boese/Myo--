<?php

  $GLOBALS['pageInfo']['stylesheets'] .= "\n/admin/image/doc.css";

  global $_URL;

  $table = 'xk_files';

  if (count($_POST) > 0) {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: /admin/{$_URL[2]}");
    
  } else if (isset($_URL[3]) && in_array($_URL[3],array('download','link'))) {
    file_link($table,$_URL[2],$_URL[3],$_URL[4]);

  } else if (isset($_URL[3]) && in_array($_URL[3],array('folder'))) {
    files_list($table,$_URL[2],$_URL[4]);

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

    $parms['cat']   = (isset($parms['cat']))   ? $parms['cat']   : 'all';
    $parms['order'] = (isset($parms['order'])) ? $parms['order'] : 'name';

    list($title,$items) = retrieve_item_list($table,$parms);

?>
      <h1><?=$title;?></h1>
      <table class='edit_list'>
        <!-- tr>
          <td colspan='4'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr -->
        <tr>
          <th style='width:200px;'>
            Folder
          </th>
          <th style='width:45px;'>
            Documents
          </th>
        </tr>
<?

    while ($item = $items->fetch_object()) {

?>
        <tr>
          <td style='text-align:center;'>
            <a href='/admin/<?=$node;?>/folder/<?=$item->id;?>/'><?=$item->folder;?></a>
          </td>
          <td style='text-align:center;'>
            <?=$item->doc_count;?>
          </td>
        </tr>
<?
    }
    $items->close();
?>
      </table>

<?}

  function file_link($table,$node,$action,$id) {

    require(ABSPATH . '/plugin/tapestry/functions.php');
    
    $files = do_query("
      select
        f.*,
        d.name as folder
      from
        xk_files f
      join
        xk_directories d
          on f.directory = d.id
      where
        f.id = '$id' and
        f.deleted = '".ZERO_DATE."'");
    if ($files->num_rows == 0)
      return;
      
    $file = $files->fetch_object();

    set_time_limit(0);
    $file_path = ABSPATH."/site/doc/{$file->folder}/{$file->name}";
    if ($action == 'download')
      output_file($file_path,$file->name,true);
    else
      output_file($file_path,$file->name);

  }

  function files_list($table,$node,$id) {

    list($title,$items,$folder) = retrieve_file_list($table,$node,$id);

?>
      <h1><?=$title;?></h1>
      <table class='edit_list'>
        <tr>
          <th style='width:400px;'>
            File name
          </th>
          <th style='width:45px;'>
            &nbsp;
          </th>
        </tr>
<?

    while ($item = $items->fetch_object()) {

?>
        <tr>
          <td>
            <a href='/admin/<?=$node;?>/edit/<?=$item->id;?>/'><?=$item->name;?></a>
          </td>
          <td style='text-align:center;'>
            <a href='/admin/<?=$node;?>/download/<?=$item->id;?>/'>download</a>
          </td>
          <td style='text-align:center;'>
            <a href='/admin/<?=$node;?>/link/<?=$item->id;?>/<?=$item->name;?>'>link</a>
          </td>
        </tr>
<?
    }
    $items->close();
?>
      </table>

<?}

  function item_form($table,$node,$action,$id) {

   $actions = array(
       "add"      => "Add ".ucwords($node),
       "edit"     => "Update ".ucwords($node),
       "delete"   => "Delete This ".ucwords($node));
   if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

   $item = array();
   if ($id > 0)
      $item = retrieve_item($table,$id);

   if (!(($action != "add" && isset($item['id']))
      || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");

   $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['name']} $node".TITLE_SUFFIX;

   form_v($item);
   $tbi = 1;

?>

      <form id='update_doc_tag' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords("document");?></h2>
        <div class='field_input'>
          <div>
            <label>Folder:</label>
            <strong><?=$item['folder'];?></strong>
          </div>
          <div>
            <label>Document:</label>
            <strong><?=$item['name'];?></strong>
          </div>
          <div>
            <label>Description:</label>
            <?=nl2br($item['description']);?>
          </div>


        </div>

        <div class='buttons'>
          <?=input_field('reset',$tbi++,null,'reset','Back',null,null,null,
              "onclick='window.location = \"/admin/$node/folder/{$item['directory']}\"'");?>
        </div>
      </form>

<?
}

  function retrieve_file_list($table,$node,$id) {
    $files = do_query("
          select
            d.name as folder,
            f.*
          from
            xk_files f
          join
            xk_directories d
              on f.directory = d.id
          where
            d.id = '$id' and
            f.id in
              (select distinct
                 file_id
               from
                 xk_tagged_files tf
               where
                 tf.deleted = 0 and
                 tf.tag_id in ({$_SESSION[SITE_PORT]['user_tags']}))
          order by
            f.name");
    $file = $files->fetch_object();
    $title = "Folder: ".$file->folder;
    $files->data_seek(0);
    return array($title,$files,$file->folder);
  }

  function retrieve_item_list($table,$parms) {
    $sort = array(
      'name'    => 'order by name',
      'email'   => 'order by email,business_name',
      'group'   => 'order by group_name,business_name,email');
    $sortfld = $sort[$parms['order']] or $sort['name'];

    $catg = array(
      'all'     => array('query' => 1,
                         'where' => "where b.deleted = '".ZERO_DATE."'",
                         'order' => $sortfld,
                         'title' => "All Folders"),
      'latest'  => array('query' => 3,
                         'title' => 'Recent Property Adds/Updates'),
      'invalid' => array('query' => 1,
                         'where' => "where b.deleted = '".ZERO_DATE."' and
                                    ((length(email_contact) > 0 and not email_contact REGEXP '^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$') or
                                     (length(email_public)  > 0 and not email_public  REGEXP '^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$') or
                                     (length(business_name) = 0))",
                         'order' => $sortfld,
                         'title' => "Invalid Emails & Blank Property Names" ));
    $category = $catg[$parms['cat']] or $catg['all'];

    if (isset($parms['catid'])) {
      $category['where'] .= "and b.id in
                               (select property_id
                                  from property_type
                                 where status_id = '{$parms['catid']}' and
                                       deleted = '".ZERO_DATE."')";
      $catnm = do_query("select name from status where id = '{$parms['catid']}'");
      $catnam = $catnm->fetch_object();
      $category['title'] = "Category: $catnam->name";
    }

    if (isset($parms['search'])) {
      $search_for = str_replace('+',' ',$parms['search']);
      $category['where'] .= " and
            match(b.business_name,b.business_name_2,b.contact_name_first,b.contact_name_last)
              against('".add_slashes($search_for)."') ";
      $category['title'] .= " &mdash; $search_for";
      $relevance = "
            match(b.business_name,b.business_name_2,b.contact_name_first,b.contact_name_last)
              against('".add_slashes($search_for)."') as relevance, ";
      $category['order']  = "order by relevance desc";
    }

    switch ($category['query']) {
      case 1:
        return array($category['title'],do_query("
          select
            d.id,
            d.name as folder, $relevance
            count(*) doc_count
          from
            $table f
          join
            xk_directories d
              on f.directory = d.id
          where
            f.id in
              (select distinct
                 file_id
               from
                 xk_tagged_files tf
               where
                 tf.deleted = 0 and
                 tf.tag_id in ({$_SESSION[SITE_PORT]['user_tags']}))
          group by
            d.id
          order by
            folder"));
      case 3:
        return array($category['title'],do_query("
          select
            b.*,
            ctg.categories,
            greatest(b.created,b.updated,ifnull(ctg.cat_updated,'".ZERO_DATE."')) as last_updated
          from
            $table b
          join
            ($join) ctg
          on
            ctg.id = b.id
          having
            last_updated > timestampadd(day,-7,now())
          order by
            last_updated desc"));
    }
  }
  
  function retrieve_item($table,$id) {
    $item = do_query("
      select
        t.*,
        f.name as folder,
        cre.username as created_by_name,
        upd.username as updated_by_name,
        del.username as deleted_by_name
      from
        $table t
      join
        xk_directories f
          on t.directory = f.id
      left join staff cre on t.created_by = cre.id
      left join staff upd on t.updated_by = upd.id
      left join staff del on t.deleted_by = del.id
      where
        t.id='$id'");
    $row = $item->fetch_array();

    $tags = do_query("
      select
        *
      from
        xk_tagged_files
      where
        deleted = '".ZERO_DATE."' and
        file_id = '$id'");
    $row['tags'] = array();
    while($tag = $tags->fetch_object())
      $row['tags'][] = $tag->tag_id;
    return $row;
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
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {

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
    //add_slashes($_POST);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset','tag'))) {
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
    $GLOBALS['_URL'][2] .= "/folder/{$item['directory']}";
    
    $tags = do_query("
        select
          group_concat(tag_id) as tags
        from
          xk_tagged_files
        where
          deleted = '".ZERO_DATE."' and
          file_id = '$id'");
    $tag = $tags->fetch_array();
    $oldtags = ($tag['tags'] == '') ? array() : explode(',',$tag['tags']);
    $newtags = (isset($_POST['tag']) && is_array($_POST['tag'])) ? $_POST['tag'] : array();
    $tags_added     = array_diff($newtags,$oldtags);
    $tags_deleted   = array_diff($oldtags,$newtags);

    if (count($tags_deleted) > 0)
      do_query("
        update
          xk_tagged_files
        set
          deleted = now(),
          deleted_by = '".$_SESSION[SITE_PORT]['user_id']."'
        where
          deleted = '".ZERO_DATE."' and
          file_id = '$id' and
          tag_id in (".implode(',',$tags_deleted).")");

    if (count($tags_added) > 0) {
      foreach ($tags_added as $add)
        $adds[] = "('$id','$add',now(),'".$_SESSION[SITE_PORT]['user_id']."')";
      do_query("
        insert into
          xk_tagged_files
           (file_id,tag_id,created,created_by)
          values
           ".implode(',',$adds));
    }

  }

?>