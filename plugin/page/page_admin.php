<?php
  require(ABSPATH . 'plugin/spchamber/functions.php');

  global $_URL;

  $table = 'page';

  if (isset($_POST['action_step']) && $_POST['action_step'] == "process") {
    db_update($table,$_URL[3],$_URL[4]);
    if (isset($GLOBALS['next_page']))
      header("Location: {$GLOBALS['next_page']}");
    else
      header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && in_array($_URL[3],array('clone'))) {
    db_update($table,$_URL[3],$_URL[4]);
    header("Location: {$GLOBALS['next_page']}");

  } else if (isset($_URL[3]) && in_array($_URL[3],array('swap'))) {
    db_update($table,$_URL[3],$_URL[4],$_URL[5]);
    header("Location: ".SITE_HTTP."/admin/{$_URL[2]}");

  } else if (isset($_URL[3]) && $_URL[3] != "") {
    item_form($_URL[2],$_URL[3],$_URL[4]);

  } else
    items_list($_URL[2]);


  function items_list($node) {

?>
      <h1><?=ucwords("{$node}s");?></h1>
      <table class='edit_list page_admin'>
        <tr>
          <td colspan='6'>
            <a class='btn' href='/admin/<?=$node;?>/add/'>Add <?=$node;?></a>
          </td>
        </tr>
        <tr>
          <th class='arrows'>
            &nbsp;
          </th>
          <th colspan='2' style='width:250px;'>
            Title
          </th>
          <th style='width:125px;'>
            Address
          </th>
          <th style='width:70px;'>
            Status
          </th>
          <th>
            Controls
          </th>
        </tr>
<?

    $items = retrieve_item_list();
    
    $dupes = array();
    $dupelist = retrieve_dupe_addresses();
    while ($dupes = $dupelist->fetch_object())
      $dupe[$dupes->file_name] = " dupe";

    while ($p = $items->fetch_object ()) {
      $item_name[$p->id] = htmlentities($p->menu_name);
      if ($p->indent == 0) {
        if (isset($prev_main_id)) {
          $main_prev[$p->id] = $prev_main_id;
          $main_next[$prev_main_id] = $p->id;
        }
        $prev_main_id = $p->id;
        if (isset($prev_sub_id)) {unset($prev_sub_id);}
      }
      else {
        if (isset($prev_sub_id)) {
          $sub_prev[$p->id] = $prev_sub_id;
          $sub_next[$prev_sub_id] = $p->id;
        }
        $prev_sub_id = $p->id;
        $item_count++;
      }
    }
    
    $non_indented_title = "
          <td colspan='2' %s>
            %s
          </td>";
    $indented_title = "
          <td class='arrows'>
            %s
          </td>
          <td %s style='width:210px;'>
            %s
          </td>";
    $up_arrow_code = "
            <a class='btn' title='Move %s above %s' href='/admin/$node/swap/%d/%d/'><img src='/admin/image/arrow_up.gif' alt=''/></a>";
    $down_arrow_code = "
            <a class='btn' title='Move %s below %s' href='/admin/$node/swap/%d/%d/'><img src='/admin/image/arrow_down.gif' alt=''/></a>";
    $no_arrow_code = "
            <img class='blank_arrow' src='/admin/image/arrow_blank.gif' alt=''/>";

    $items->data_seek(0);
    while ($item = $items->fetch_array()) {

      $id = $item['id'];$main_arrows = '';$sub_arrows = '';$name_class = array();
      if ($item['display_link'] == 1) $name_class[] = 'in_menu';
      if (count($name_class) > 0) $name_class = 'class="'.implode(' ',$name_class).'"'; else $name_class = '';
      if ($item['indent'] == 0) {
        if (isset($main_prev[$id]))
          $main_arrows .= sprintf($up_arrow_code,$item_name[$id],$item_name[$main_prev[$id]],$id,$main_prev[$id]);
        else
          $main_arrows .= $no_arrow_code;
        if (isset($main_next[$id]))
          $main_arrows .= sprintf($down_arrow_code,$item_name[$id],$item_name[$main_next[$id]],$id,$main_next[$id]);
        else
          $main_arrows .= $no_arrow_code;
        $title = sprintf($non_indented_title,$name_class,$item_name[$id]);
      } else {
        if (isset($sub_prev[$id]))
          $sub_arrows .= sprintf($up_arrow_code,$item_name[$id],$item_name[$sub_prev[$id]],$id,$sub_prev[$id]);
        else
          $sub_arrows .= $no_arrow_code;
        if (isset($sub_next[$id]))
          $sub_arrows .= sprintf($down_arrow_code,$item_name[$id],$item_name[$sub_next[$id]],$id,$sub_next[$id]);
        else
          $sub_arrows .= $no_arrow_code;
        $title = sprintf($indented_title,$sub_arrows,$name_class,$item_name[$id]);
      }
      $show_page = $item['file_name']; if ($show_page == '.') $show_page = '&nbsp;(home page)&nbsp;';
      $pagelink = sprintf("<a href='/%s/'>%s</a>",$item['file_name'],$show_page);

?>
        <tr class='<?=$item['status'].$dupe[$item['file_name']];?>'>
          <td class='arrows'>
            <?=$main_arrows;?>
          </td><?=$title;?>
          
          <td>
            <?=$pagelink;?>
          </td>
          <td style='text-align:center;'>
            <?=$item['status'];?>
          </td>
          <td>
            <a class='btn' href='/admin/<?=$node;?>/edit/<?=$item['id'];?>/'>Edit</a>
            <a class='btn' href='/admin/<?=$node;?>/clone/<?=$item['id'];?>/'>Clone</a>
            <a class='btn' href='/admin/<?=$node;?>/delete/<?=$item['id'];?>/'>Delete</a><br/>
          </td>
        </tr>
<?  }  ?>
      </table>

<?}

  function item_form($node,$action,$id) {
    set_form_referer();

    $actions = array(
       "add"      => "Add ".ucwords($node),
       "edit"     => "Update ".ucwords($node),
       "delete"   => "Delete This ".ucwords($node));
    if (!isset($actions[$action]))
      exit("<h1>Invalid action</h1>");

    $item = array();
    if ($id > 0)
      $item = retrieve_item($id);

    if (!(($action != "add" && isset($item['id']))
       || ($action == "add" && !isset($item['id']))))
      exit("<h1>Invalid action</h1>");
      
    $GLOBALS['pageInfo']['title'] = ucwords($action)." {$item['title']} $node".TITLE_SUFFIX;
    $GLOBALS['pageInfo']['scripts'] .= "\n/site/script/page-name.js\n/xtool/ckeditor/ckeditor.js";

    $other_pages = retrieve_item_list($id);
    $display_after = 0;
    if (!isset($item['id']))
      $item['display_order'] = 999999;
    while ($other = $other_pages->fetch_array()) {
      if ($other['display_order'] < $item['display_order'])
        $display_after = $other['id'];
    }

    $option_code = "
              <option value=\"%s\" %s>%s%s%s</option>";
    if ($display_after == 0) $slctd = 'selected="selected"';
    $display_after_opts = sprintf($option_code,'0',$slctd,'-- ','Put this page at the top','--');
    $other_pages->data_seek(0);
    while ($other = $other_pages->fetch_array()) {
      list($slctd,$prefix,$suffix) = array('','','');
      if ($other['id'] == $display_after)
        $slctd = 'selected="selected"';
      if ($other['indent'] == "1")
        $prefix = '--';
      if ($other['status'] <> "active")
        $suffix = " ({$other['status']})";
      $display_after_opts .= sprintf($option_code,
          $other['id'],$slctd,$prefix,form_v($other['menu_name']),$suffix);
    }

    $option_code = "
              <option value='%s' %s>%s</option>";
    $templates = do_query("
      select
        *
      from
        template
      where
        id > 1 and
        deleted = '".ZERO_DATE."'
      order by
        name");
    while ($template = $templates->fetch_array()) {
      $slctd = '';
      if ($template['id'] == $item['template'])
        $slctd = 'selected="selected"';
      $template_opts .= sprintf($option_code,$template['id'],$slctd,$template['name']);
    }
    
    $statuses = array('active','draft','inactive','admin');
    if (!in_array($item['status'],$statuses)) $item['status'] = $statuses[0];
    foreach($statuses as $stat) {
      $slctd = '';
      if ($stat == $item['status'])
        $slctd = 'selected="selected"';
      $status_opts .= sprintf($option_code,$stat,$slctd,$stat);
    }

    $checked = array(
        "0" => array("0" => "checked='checked'","1" => ""),
        "1" => array("0" => "","1" => "checked='checked'"));
        
    if ($item['sub_id'] > 0)
      $is_sub_page = 1;
    else
      $is_sub_page = 0;

    $this_page_content = htmlspecialchars($item['content']);
    form_v($item);
    $tbi = 1;
    
    $cancel_to = (isset($_SESSION['form_referer'])) ?
                 $_SESSION['form_referer']
               : SITE_HTTP."/admin/$node";
?>

      <form id='update_page' class='edit_item' method='post' action='<?=_URI_?>'>
        <h2><?=ucwords($action);?> <?=ucwords($node);?></h2>
        <div class='field_input'>
          <div id='header_photo_div'>
            <img id='header_photo_img' alt='' src='<?=$item['header_photo'];?>' />
          </div>
          <div>
            <label for="file_name">Page address:</label>
            <input id="file_name" name="file_name" type="text" tabindex='<?=$tbi++;?>'
              style="width:300px;" value="<?=$item['file_name'];?>" onkeyup="cleanupName('file_name');" />
          </div>
          <div>
            <label for="title">Title:</label>
            <input id="title" name="title" type="text" tabindex='<?=$tbi++;?>' style="width:300px;" value="<?=$item['title'];?>" />
          </div>
          <div>
            <label for='display_after'>Display after:</label>
            <select id='display_after' name="display_after" tabindex='<?=$tbi++;?>' style="width:300px;"><?=$display_after_opts;?>

            </select>
            <input id="display_after_orig" name="display_after_orig" type="hidden" value="<?=$display_after;?>" />
          </div>
          <div>
            <label>This is a sub-page?</label>
            <input type="radio" name="is_sub_page" id="sub_page_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$is_sub_page];?> />
              <label for="sub_page_true" class="radio">Yes</label>
            <input type="radio" name="is_sub_page" id="sub_page_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$is_sub_page];?> />
              <label for="sub_page_false" class="radio">No</label>
            <input id="sub_page_orig" name="sub_page_orig" type="hidden" value="<?=$is_sub_page;?>" />
          </div>
          <div>
            <label>Include in menu?</label>
            <input type="radio" name="display_link" id="in_menu_true" value="1" tabindex='<?=$tbi++;?>'
              <?=$checked['1'][$item['display_link']];?> />
              <label for="in_menu_true" class="radio">Yes</label>
            <input type="radio" name="display_link" id="in_menu_false" value="0" tabindex='<?=$tbi++;?>'
              <?=$checked['0'][$item['display_link']];?> />
              <label for="in_menu_false" class="radio">No</label>
          </div>
          <div>
            <label for="menu_name">Name in menu:</label>
            <input id="menu_name" name="menu_name" type="text" tabindex='<?=$tbi++;?>' style="width:300px;" value="<?=$item['menu_name'];?>" />
          </div>
          <div>
            <label for='header_photo'>Header photo:</label>
            <input id="header_photo" name="header_photo" type="text" tabindex='<?=$tbi++;?>'
              style="width:200px;" value="<?=$item['header_photo'];?>" />
            <input type='button' value='Browse Server' id='browsebtn' tabindex='<?=$tbi++;?>' onclick='fire_KFM();' />
            <script>
              function fire_KFM() {
  			          window.SetUrl=(function(){
  				          return function(value){
                      if ((value.substr,0,5) != '/site')
                        value = value.substr(value.search('/site'));
                      segs = value.split('/');
                      file = segs.pop();
                      if (file.search(/[.]/) < 1)
                        value = value.concat('.jpg');
  					          document.getElementById('header_photo').value = value;
  					          document.getElementById('header_photo_img').src = value;
  				          }
  			          })(this.id);
                  var kfm_url='/xtool/kfm/index.php';
  			          var kfmwindow = window.open(kfm_url,'kfm','modal,width=800,height=400');
  			          kfmwindow.focus();
              };
            </script>
          </div>
          <div>
            <label for='status'>Status:</label>
            <select id='status' name="status" tabindex='<?=$tbi++;?>'><?=$status_opts;?>

            </select>
          </div>
          <div>
            <label for="header">Page heading:</label>
            <input id="header" name="header" type="text" tabindex='<?=$tbi++;?>' style="width:300px;" value="<?=$item['header'];?>" />
          </div>
          <div>
            <label for="sub_head">Sub head:</label>
            <textarea id='sub_head' name="sub_head" cols='80' rows='3'
              style='width:500px;height:6em;' tabindex='<?=$tbi++;?>'><?=$item['sub_head'];?></textarea>
          </div>
        </div>
        <div class='wysiwyg'>
          <textarea name='content_edit' id='content_edit' rows='5' cols='80' tabindex='<?=$tbi++;?>'><?=$this_page_content;?></textarea>
          <script type='text/javascript'>
            CKEDITOR.replace('content_edit',
              {
                customConfig : '/admin/ckeditor/config.js',
                toolbar : 'Page'
              });
          </script>
        </div>
        <div class='field_input'>
          <div>
            <label for='template'>Template:</label>
            <select id='template' name="template" tabindex='<?=$tbi++;?>'><?=$template_opts;?>

            </select>
          </div>
          <div>
            <label for='meta_description'>Meta description:</label>
            <textarea id='meta_description' name="meta_description" cols='80' rows='3'
              style='width:500px;height:4em;' tabindex='<?=$tbi++;?>'><?=$item['meta_description'];?></textarea>
          </div>
          <div>
            <label for='meta_keywords'>Meta keywords:</label>
            <textarea id='meta_keywords' name="meta_keywords" cols='80' rows='3'
              style='width:500px;height:4em;' tabindex='<?=$tbi++;?>'><?=$item['meta_keywords'];?></textarea>
          </div>
        </div>
        <div class='buttons'>
          <input type='submit' name='enter' value='<?=$actions[$action];?>' tabindex='<?=$tbi++;?>'/>
          <input type='reset'  name='reset'  value='Cancel' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?=$cancel_to;?>"' />
          <input type='hidden' name='action_step' value='process' />
        </div>
        <ul class='date_block'><?=date_block($item);?>

        </ul>
      </form>

<?
}

  function retrieve_item_list($skip_page = '') {
    if ($_SESSION[SITE_PORT]['user_type'] == 3 && !($skip_page > 0))
      $pagelimits = "and p.id in ({$_SESSION[SITE_PORT]['user_pages']})";
    else if ($_SESSION[SITE_PORT]['user_type'] == 3)
      $pagelimits = "and p.id <> '$skip_page'";
    else if ($skip_page > 0)
      $pagelimits = "and p.id <> '$skip_page'";

    return do_query("
     select
        p.id as id,
        p.display_order,
        0 as indent,
        case
          when p.display_link = 1
            then p.menu_name
          else p.title
        end as menu_name,
        p.display_link,
        p.status,
        p.file_name
      from
        page p
      where
        (p.deleted = 0) and
        (p.sub_id = 0) $pagelimits

      union

      select
        p.id as id,
        p.display_order,
        1 as indent,
        case
          when p.display_link = 1
            then p.menu_name
          else p.title
        end as menu_name,
        p.display_link,
        p.status,
        p.file_name
      from
        page p
      join
        page m
          on p.sub_id = m.id
      where
        p.sub_id > 0 and
        p.deleted = 0 and
        m.deleted = 0 $pagelimits

      order by
        display_order");
  }

  function retrieve_item($id) {
    $item = do_query("
      select
        p.*,
        cre.username as created_by_name,
        upd.username as updated_by_name,
        del.username as deleted_by_name
      from
        page p
      left join staff cre on p.created_by = cre.id
      left join staff upd on p.updated_by = upd.id
      left join staff del on p.deleted_by = del.id
      where
        p.id = '$id'");
    return $item->fetch_array();
  }

  function retrieve_dupe_addresses() {
    return do_query("
      select
        p.file_name,
        count(*)
      from
        page p
      left join
        page m
          on p.sub_id = m.id
      where
        p.deleted = 0 and
        p.status <> 'inactive' and
        ifnull(m.deleted,0) <> 0
      group by
        p.file_name
      having
        count(*) > 1");
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
      case "clone":
        clone_item($table,$id);
        break 1;
      case "update":
        update_embedded($table,$id);
        break 1;
      case "delete":
        delete_item($table,$id);
        break 1;
      case "swap":
        swap_item($table,$id,$id2,'display_order','display_order_prev','sub_id');
        break 1;
      default:
        exit("<h1>Invalid update</h1>");
    }
  }

  function insert_item($table) {

    $disp_order = new_display_order($table,0,$_POST['display_after'],'display_order');
    $sub_of = new_member_of($table,$_POST['is_sub_page'],$disp_order,'display_order','sub_id');

    add_slashes($_POST);

    do_query("
      insert
        into page
          (file_name,
           title,
           display_order,
           sub_id,
           display_link,
           menu_name,
           status,
           header,
           header_photo,
           sub_head,
           content,
           template,
           meta_description,
           meta_keywords,
           created,
           created_by)
         values
          ('{$_POST['file_name']}',
           '{$_POST['title']}',
           '$disp_order',
           '$sub_of',
           '{$_POST['display_link']}',
           '{$_POST['menu_name']}',
           '{$_POST['status']}',
           '{$_POST['header']}',
           '{$_POST['header_photo']}',
           '{$_POST['sub_head']}',
           '{$_POST['content_edit']}',
           '{$_POST['template']}',
           '{$_POST['meta_description']}',
           '{$_POST['meta_keywords']}',
           now(),
           '{$_SESSION[SITE_PORT]['user_id']}')");

    $insert_id = $GLOBALS['db']->insert_id;
    resequence_table($table,'display_order','sub_id');

    if($_SESSION[SITE_PORT]['user_type'] == 3) {
       do_query(
         "update
            staff
          set
            pages =
              case
                when pages = ''
                  then '$insert_id'
                else
                  concat(pages,',$insert_id'
              end
          where
            id='{$_SESSION[SITE_PORT]['user_id']}'");
    }
    
    if ($_POST['status'] == "active")
      insert_site_content($_POST['file_name'],"content");

  }

  function clone_item($table,$id) {
    $clone_rec = do_query("
      select
        *
      from
        page
      where
        id = '$id'");
    if ($clone_rec->num_rows <> 1)
      exit("<h1>unable to find page #$id to clone</h1>");
    $clone = $clone_rec->fetch_object();
    
    $disp_order = new_display_order($table,0,$id,'display_order');
    $sub_of = new_member_of($table,$clone->is_sub_page,$disp_order,'display_order','sub_id');

    $fields = do_query("show columns from page");
    $fld    = $fields->fetch_object();
    while ($fld = $fields->fetch_object())
      $field[] = $fld->Field;
    do_query("
      insert
        into page
          (".implode(',',$field).")
        (select
           ".implode(',',$field)."
           from page where id = '$id')");
    $new_id = $GLOBALS['db']->insert_id;

    do_query("
      update
        page
      set
        file_name     = concat(file_name,'-clone'),
        title         = concat(title,' (clone)'),
        menu_name     = concat(menu_name,' (clone)'),
        display_order = '$disp_order',
        sub_id        = '$sub_of',
        status        = 'draft',
        created       = now(),
        created_by    = '{$_SESSION[SITE_PORT]['user_id']}',
        updated       = '".ZERO_DATE."',
        updated_by    = 0
      where
        id='$new_id'");

    resequence_table($table,'display_order','sub_id');

    if($_SESSION[SITE_PORT]['user_type'] == 3) {
       do_query(
         "update
            staff
          set
            pages =
              case
                when pages = ''
                  then '$insert_id'
                else
                  concat(pages,',$insert_id'
              end
          where
            id='{$_SESSION[SITE_PORT]['user_id']}'");
    }
    
    $GLOBALS['next_page'] = SITE_HTTP."/admin/page/edit/$new_id";
  }

  function update_item($table,$id) {

    $page = do_query("
      select
        *
      from
        $table
      where
        id = '$id'");
    $page = $page->fetch_array();

    $disp_order = new_display_order($table,$page['display_order'],$_POST['display_after'],'display_order');
    $sub_of     = new_member_of($table,$_POST['is_sub_page'],$disp_order,'display_order','sub_id');

    add_slashes($_POST);

    do_query("
      update
        page
      set
        file_name     = '{$_POST['file_name']}',
        title         = '{$_POST['title']}',
        display_order = '$disp_order',
        sub_id        = '$sub_of',
        display_link  = '{$_POST['display_link']}',
        menu_name     = '{$_POST['menu_name']}',
        status        = '{$_POST['status']}',
        header        = '{$_POST['header']}',
        header_photo  = '{$_POST['header_photo']}',
        sub_head      = '{$_POST['sub_head']}',
        content       = '{$_POST['content_edit']}',
        template      = '{$_POST['template']}',
        meta_description = '{$_POST['meta_description']}',
        meta_keywords = '{$_POST['meta_keywords']}',
        updated       = now(),
        updated_by    = '{$_SESSION[SITE_PORT]['user_id']}'
      where
        id='$id'");
        
    resequence_table($table,'display_order','sub_id');
    retrieve_form_referer();
    
    if ($_POST['file_name'] == '.')
      $_POST['file_name'] = '';
    if ($_POST['status'] == 'active' && $page['status'] == 'active')
      update_site_content($_POST['file_name'],"content",$_POST['file_name']);
    else if ($_POST['status'] == 'active')
      insert_site_content($_POST['file_name'],"content");
    else if ($page['status'] == 'active')
      delete_site_content($page['file_name']);
  }

  function update_embedded($table,$id) {

    add_slashes($_POST);

    do_query("
      update
        page
      set
        content       = '{$_POST['content_edit']}',
        updated       = now(),
        updated_by    = '{$_SESSION[SITE_PORT]['user_id']}'
      where
        id='$id'");

    $result = do_query("
      select
        file_name
      from
        page
      where
        id='$id'");
    $page = $result->fetch_object();

    $GLOBALS['next_page'] = SITE_HTTP.'/'.$page->file_name;
    
    if ($page->file_name == '.')
      $page->file_name = '';
    update_site_content($page->file_name,"content",$page->file_name);

  }


?>