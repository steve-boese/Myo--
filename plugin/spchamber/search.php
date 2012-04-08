<?php
  //require(ABSPATH . 'plugin/spchamber/functions.php');

  global $_URL;
  
  $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/search.css";
  
  $table = 'site_content';

  if (count($_POST) > 0) {
    search_for($table,$_URL[1]);
    header("Location: ".SITE_HTTP."/{$_URL[1]}/{$_URL[2]}");

  } else if (isset($_URL[2]) && $_URL[2] == 'catid:all') {
    cat_list($table,$_URL[1]);

    // dormant code -- could be used for single-business pages
  } else if (isset($_URL[2]) && $_URL[2] == 'member') {
    item_form($table,$_URL[1],$_URL[3]);

  } else {
    $parms = array();
    for ($i=2; $i<count($_URL); $i++) {
      if ($_URL[$i] <> '') {
        $parm = explode(':',$_URL[$i]);
        $parms[$parm[0]] = $parm[1];
      }
    }
    items_list($table,$_URL[1],$parms);
  }

  function items_list($table,$node,$parms) {

    if (count($parms) > 0) {
      $parms['cat']   = (isset($parms['cat']))   ? $parms['cat']   : 'all';
      $parms['order'] = (isset($parms['order'])) ? $parms['order'] : 'name';
      list($title,$items) = retrieve_item_list($table,$parms);
      $GLOBALS['pageInfo']['title'] = $title.' '.TITLE_SUFFIX;
    }

    list_header($table,$node,$parms,$title,$items,5,"businesses");
    
    if (is_object($items)) {

      echo "
        <div id='search_results'>";

      if ($items->num_rows == 0)
        echo "
          <div>
            No matches found
          </div>";
      else {
        echo "
            <ul>";
        while ($item = $items->fetch_object()) {
          echo "
              <li>
                <a href='$item->url'>$item->title</a>
              </li>";
        }
        echo "
            </ul>";
      }
      $items->close();
      echo "
        </div>";
    }
  }
  

  function list_header($table,$node,$parms,$title,$items,$cols,$class) {
    $search_for = str_replace(array('+','"'),array(' ','&quot;'),urldecode($parms['search']));
    
?>
      <div id="search_keyword">
        <form method="post" id="search_form" action="/<?=$node?>/">
          <div>
            <label for="search_for">Search for:</label>
            <input type="text" name="search_for" id="search_for" style="width:270px;" value="<?=$search_for;?>"/>
            <input type="image" src="/site/image/layout/search-button.jpg" alt="" class="button" />
          </div>
        </form>
      </div>
      <h2><?=$title;?></h2>

<?
  }

  function item_form($table,$node,$action,$id) {

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

    $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['business_name']} $node".TITLE_SUFFIX;

    form_v($item);
    $tbi = 1;

?>

      <form id='update_business' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> Business or Organization</h2>
        <div class='field_input'>
          <div><?=input_field('business_name',$tbi++,'Business/org name:',null,$item['business_name'],'400px');?>
          </div>
          <div><?=input_field('business_name_2',$tbi++,'Second name:','textarea',$item['business_name_2'],'400px','height:4.2em');?>
          </div>
          <div><?=input_field('contact_name_first',$tbi++,'Contact first, last name, role:',null,$item['contact_name_first'],'128px');?>
               <?=input_field('contact_name_last',$tbi++,null,null,$item['contact_name_last'],'128px');?>
               <?=input_field('contact_role',$tbi++,null,null,$item['contact_role'],'128px');?>
          </div>
          <div><?=input_field('email_contact',$tbi++,'Contact, public email:',null,$item['email_contact'],'196px');?>
               <?=input_field('email_public',$tbi++,null,null,$item['email_public'],'196px');?>
          </div>
          <div><?=input_field('address',$tbi++,'Street address:','textarea',$item['address'],'400px','height:2.7em');?>
          </div>
          <div><?=input_field('city',$tbi++,'City, state, zip:',null,$item['city'],'154px');?>
               <?=input_field('state',$tbi++,null,null,$item['state'],'35px');?>
               <?=input_field('zip',$tbi++,null,null,$item['zip'],'90px');?>
          </div>
          <div><?=input_field('phone_1',$tbi++,'Phone, description:',null,$item['phone_1'],'196px');?>
               <?=input_field('phone_1_desc',$tbi++,null,null,$item['phone_1_desc'],'196px');?>
          </div>
          <div><?=input_field('phone_2',$tbi++,'Alternate phone, description:',null,$item['phone_2'],'196px');?>
               <?=input_field('phone_2_desc',$tbi++,null,null,$item['phone_2_desc'],'196px');?>
          </div>
          <div><?=input_field('phone_fax',$tbi++,'Fax:',null,$item['phone_fax'],'196px');?>
          </div>
          <div><?=input_field('website',$tbi++,'Website:',null,$item['website'],'400px');?>
          </div>
          <div><?=input_field('blurb',$tbi++,'Descriptive blurb:','textarea',$item['blurb'],'400px','height:7.2em');?>
          </div>
          <div><?=input_field('internal_notes',$tbi++,'Internal notes:','textarea',$item['internal_notes'],'400px','height:7.2em');?>
          </div>
        </div>
        <div class='buttons'>
          <?=input_field('enter',$tbi++,null,'submit',$actions[$action]);?>
          <?=input_field('reset',$tbi++,null,'reset','Cancel',null,null,null,
              "onclick='window.location = \"".SITE_HTTP."/admin/$node\"'");?>
        </div>
<?

    $checkbox_code = "
        <li %s>
          <input name=\"categ[%s]\" id=\"categ%s\"  type=\"checkbox\" value=\"%s\" tabindex=\"%s\" %s/>
          <label for=\"categ%s\">%s</label>
        </li>";

    $the_cats = array(1,5,14);
    foreach ($the_cats as $the_cat) {
      $categ = get_members_of($the_cat);
      while ($catg = $categ->fetch_object()) {
        list($style,$checked) = array('','');
        if (is_array($item['category']) && in_array($catg->id,$item['category']))
          list($style,$checked) = array("class=\"selected\"","checked=\"checked\"");
        $the_cat_opts[$the_cat][] = sprintf($checkbox_code,$style,$catg->id,
                   $catg->id,$catg->id,$tbi++,$checked,$catg->id,$catg->name);
      }
    }
    $business_types = "\n<ul>".implode("\n",$the_cat_opts[1])."\n</ul>\n";
    $resource_types = "\n<ul>".implode("\n",$the_cat_opts[5])."\n</ul>\n";

    $col_size = ceil(count($the_cat_opts[14])/6);
    foreach($the_cat_opts[14] as $bus_cat) {
      $i++;
      $cell = floor(($i-1)/$col_size);
      $cat[$cell][] = $bus_cat;
    }
    foreach($cat as $ct)
      $c[] = "\n<ul>".implode("\n",$ct)."\n</ul>\n";
    $business_categories = "
        <td>".implode("</td><td>",$c)."</td>";

?>

        <table class="categories">
          <tr>
            <th>
              Site<br/>
              Pages
            </th>
            <th colspan="6">
              Categories
            </th>
          </tr>
          <tr>
            <td>
              <table>
                <tr>
                  <td>
                    <?=$business_types;?>
                  </td>
                </tr>
                <tr>
                  <td>
                    &nbsp;
                  </td>
                </tr>
                <tr>
                  <th>
                    Local<br/>
                    Resource<br/>
                    Types
                  </th>
                </tr>
                <tr>
                  <td>
                    <?=$resource_types;?>
                  </td>
                </tr>
              </table>
            </td>
            <?=$business_categories;?>
          </tr>
        </table>
        <ul class='date_block'><?=date_block($item);?>

        </ul>
      </form>

<?
}

  function retrieve_item_list($table,$parms) {

    if (!isset($parms['search']))
      return array($GLOBALS['pageInfo']['title'],null);
      
    if (isset($parms['search'])) {
      $search_for = str_replace('+',' ',$parms['search']);
      $category['where'] .= " and
            match(b.business_name,b.business_name_2,b.contact_name_first,b.contact_name_last,synonyms)
              against('".addslashes(urldecode($search_for))."') ";
      $category['title'] .= " &mdash; $search_for";
      $relevance = "
            match(b.business_name,b.business_name_2,b.contact_name_first,b.contact_name_last,synonyms)
              against('".addslashes(urldecode($search_for))."') as relevance, ";
      $category['order']  = "order by relevance desc";
    }

    $items = do_query("
      select
        sc.*,
        match(sc.title,sc.content_plain)
          against('".addslashes(urldecode($search_for))."') as relevance
      from
        $table sc
      where
        match(sc.title,sc.content_plain)
          against('".addslashes(urldecode($search_for))."')
      order by
        relevance desc");
    $show_search_for = str_replace(array('+','"'),array(' ','&quot;'),urldecode($search_for));
    if ($items->num_rows == 0)
      $title = "Search result &ndash; $show_search_for";
    else if ($items->num_rows == 1)
      $title = $items->num_rows." match &ndash; $show_search_for";
    else
      $title = $items->num_rows." matches &ndash; $show_search_for";
    return array($title,$items);
    
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
    $row = $item->fetch_array();
    $categs = do_query("
      select
        *
      from
        business_type
      where
        deleted = '".ZERO_DATE."' and
        business_id = '$id'");
    $row['category'] = array();
    while($cat = $categs->fetch_object())
      $row[category][] = $cat->status_id;
    return $row;
  }

  function cat_list($table,$node) {

    $title = 'Business Categories';
    $items = retrieve_cat_list($table);

    $GLOBALS['pageInfo']['title'] = $title.' '.TITLE_SUFFIX;

    list_header($table,$node,$parms,$title,$items,6,"cat_links");

    $cell_start = "
      <td>";
    $list_start = "
        <ul>";
    $categ_code = "
          <li><a href='/$node/catid:%s/'>%s (%s)</a></li>";
    $list_end = "
        </ul>";
    $cell_end = "
      </td>";

    $the_cats = array(14);
    foreach ($the_cats as $the_cat) {
      $categ = get_org_counts($the_cat);
      while ($catg = $categ->fetch_object()) {
        $the_cat_opts[$the_cat][] = sprintf($categ_code,$catg->id,
                   $catg->name,$catg->org_count);
      }
    }

    $col_size = ceil(count($the_cat_opts[14])/5);
    foreach($the_cat_opts[14] as $bus_cat) {
      $i++;
      $cell = floor(($i-1)/$col_size);
      $cat[$cell][] = $bus_cat;
    }
    foreach($cat as $ct)
      $c[] = $list_start.implode("",$ct)."$list_end";
    $business_categories = $cell_start.implode($cell_end.$cell_start,$c).$cell_end;

?>

  <table id='cat_list'>
    <tr><?=$business_categories;?>
    
    </tr>
  </table>

<?

  }
  
  function get_org_counts($categ) {

    return do_query("
      select
        b.status_id as id,
        s.name,
        count(*) as org_count
      from
        business_type b
      join
        status s
          on b.status_id = s.id
      where
        s.member_of = '$categ' and
        b.deleted = '".ZERO_DATE."' and
        s.deleted = '".ZERO_DATE."'
      group by
        b.status_id
      order by
        s.seq");
        
  }

  function retrieve_cat_list($table) {
    return do_query("
      select
        b.status_id,
        s.name,
        count(*) as org_count
      from
        business_type b
      join
        status s
          on b.status_id = s.id
      where
        b.deleted = '".ZERO_DATE."'
      group by
        b.status_id
      order by
        s.name");
  }


  function db_update($table,$node,$action) {
    if (isset($_POST['cancel']))
      return;
    switch ($action) {
      case "search":
        search_for($table,$node);
        break 1;
      case "add":
        insert_item($table);
        break 1;
      case "edit":
        update_item($table,$id);
        break 1;
      case "delete":
        delete_item($table,$id);
        break 1;
      case "Un-delete":
        delete_item($table,$id);
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function search_for($table,$node) {
    if (isset($_POST['search_box']))
      $_POST['search_for'] = $_POST['search_box'];
    if (trim($_POST['search_for']) == '')
      $GLOBALS['_URL'][2] = "";
    else {
      if (FORMS_ESCAPED)
        $_POST['search_for'] = stripslashes($_POST['search_for']);
      $GLOBALS['_URL'][2] = "search:".str_replace(' ','+',$_POST['search_for'])."/";
    }
  }

  function insert_item($table) {
    add_slashes($_POST);
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset','categ')) && strlen($value) > 0) {
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
    if (isset($_POST['categ']) && is_array($_POST['categ']) && count($_POST['categ']) > 0) {
      foreach($_POST['categ'] as $cat)
        $updcat[] = "('$new_id','$cat',now())";
      do_query("
        insert into
          business_type
           (business_id,status_id,created)
          values
           ".implode(',',$updcat));
    }
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
    foreach($_POST as $fld=>$value) {
      if (!in_array($fld,array('enter','reset','categ'))) {
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
    $cats = do_query("
        select
          group_concat(status_id) as cats
        from
          business_type
        where
          deleted = '".ZERO_DATE."' and
          business_id = '$id'");
    $cat = $cats->fetch_array();
    $oldcats = ($cat['cats'] == '') ? array() : explode(',',$cat['cats']);
    $newcats = (isset($_POST['categ']) && is_array($_POST['categ'])) ? $_POST['categ'] : array();
    $cats_added     = array_diff($newcats,$oldcats);
    $cats_deleted   = array_diff($oldcats,$newcats);

    if (count($cats_deleted) > 0)
      do_query("
        update
          business_type
        set
          deleted = now(),
          deleted_by = '".$_SESSION[SITE_PORT]['user_id']."'
        where
          deleted = '".ZERO_DATE."' and
          business_id = '$id' and
          status_id in (".implode(',',$cats_deleted).")");

    if (count($cats_added) > 0) {
      foreach ($cats_added as $add)
        $adds[] = "('$id','$add',now(),'".$_SESSION[SITE_PORT]['user_id']."')";
      do_query("
        insert into
          business_type
           (business_id,status_id,created,created_by)
          values
           ".implode(',',$adds));
    }
  }

?>