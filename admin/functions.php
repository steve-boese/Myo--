<?php

  function db_connect() {
    $db = @new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME,DB_PORT);
    if (mysqli_connect_errno())
      doError(mysqli_connect_error(),"mysql_connect");
    return $db;
  }

	function do_query($theSQL) {
    if ($GLOBALS['query_debug']) {
      echo "
        <strong>Executing query:</strong><div style=\"text-align:left; font-size:8pt;\"><pre>".
        htmlspecialchars($theSQL)."</pre></div>";
    }
    $result = $GLOBALS['db']->query($theSQL) or doError($GLOBALS['db']->error,$theSQL);
    return $result;
  }

  function doError($error,$query){
    if (isset($_SESSION["returnerror"])) {
      return;}
    else if (SITE_FAIL_EMAIL) {
    	$headers  =
        "MIME-Version: 1.0\r\n".
        "Content-type: text/html; charset=iso-8859-1\r\n".
	      "From: ".SITE_EMAIL_FROM."\r\n";
      $message =
        "<div style=\"text-align:left; font-size:8pt;\">\n".
        "<pre>\n".
        date("r")."\n\n".
        "Error: \n   $error\n".
        "Query: \n   ".str_replace(",",",\n  ",$query)."\n\n".
        print_r($_SERVER,true).
        "</pre>\n".
        "</div>\n";
      mail(SITE_TECH_EMAIL,"DB error at ".SITE_VERSION." ".SITE_NAME." site",$message,$headers);
      exit(SITE_FAIL_MESSAGE);
    } else {
      echo
        "<div style=\"text-align:left; font-size:8pt;\">\n".
        "<pre>\n".
        "Error: \n   $error\n".
        "Query: \n   ".str_replace(",",",\n  ",$query)."\n".
        "</pre>\n".
        "</div>\n";
      if ($GLOBALS['debug_track'])
        $GLOBALS['track'] .=
        "<div style=\"text-align:left; font-size:8pt;\">\n".
        "<pre>\n".
        "Error: \n   $error\n".
        "Query: \n   ".str_replace(",",",\n  ",$query)."\n".
        print_r($_SERVER,true).
        "</pre>\n".
        "</div>\n";
      else
        exit;
    }
  }

  // If session has been dormant longer than max, clear session variables
  function Check_session() {
    if (isset($_SESSION[SITE_PORT]['active_at'])) {
      if ((time() - $_SESSION[SITE_PORT]['active_at']) / 60 > MAX_SESSION_LEN)
        $_SESSION[SITE_PORT] = array();
    }
    $_SESSION[SITE_PORT]['active_at'] = time();
  }

  function replace_headers($thisContent) {
    $parms = array(
      'title',
      'meta_title',
      'meta_description',
      'meta_keywords',
      'stylesheets',
      'scripts',
      'favicon');

    $code['title'] = "
  <title>%s</title>";
    $code['meta_title'] = "
  <meta name=\"title\" content=\"%s\" />";
    $code['meta_description'] = "
  <meta name=\"description\" content=\"%s\" />";
    $code['meta_keywords'] = "
  <meta name=\"keywords\" content=\"%s\" />";
    $code['favicon'] = "
  <link rel=\"shortcut icon\" href=\"%s\" type=\"image/x-icon\" />";
    $code2['stylesheets'] = "
  <link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />";
    $code2['scripts'] = "
  <script type=\"text/javascript\" src=\"%s\"></script>";

    $template = "\n<!-- pageInfo:%s -->";

    $GLOBALS['pageInfo']['meta_title'] = $GLOBALS['pageInfo']['title'];
    foreach ($parms as $parm) {
      if (isset($code[$parm])) {
        $thisContent = str_replace(
                   sprintf($template,$parm),
                   sprintf($code[$parm],$GLOBALS['pageInfo'][$parm]),
                   $thisContent);
      } elseif (isset($code2[$parm])) {
        $lines = '';
        if (isset($GLOBALS['pageInfo'][$parm]) && $GLOBALS['pageInfo'][$parm] <> '') {
          $items = nl2array($GLOBALS['pageInfo'][$parm]);
          foreach ($items as $items)
            $lines .= sprintf($code2[$parm],$items);
        }
        $thisContent = str_replace(
                   sprintf($template,$parm),
                   $lines,
                   $thisContent);
      } else echo "<h3>no code found for $parm</h3>";
    }
    return $thisContent;
  }

	function dbdate($thedate,$format=DB_DATE,$zero=ZERO_DATE) {
    $udate = strtotime($thedate);
    if (!$udate)
      return $zero;

    // if year was specified
    if (count(explode('-',$thedate)) > 2 || count(explode('/',$thedate)) > 2)
      return date($format,$udate);

    // year was not specified
    $sixmo = 182.5 * 24 * 60 * 60;
    if ($udate > time() + $sixmo)
      return date($format,$udate - ($sixmo * 2));

    return date($format,$udate);
  }

  function dbdate_show($thedate, $dow=false) {
    if ($thedate == "") return "";
    if ($thedate == ZERO_DATE) return "";
    $udate = strtotime($thedate);
    if (!$udate) return "";
    $sixmo = 182.5 * 24 * 60 * 60;
    if ($udate < time() - $sixmo || $udate > time() + $sixmo) {
      $y = "/Y";
    } else {
      if (date("i",$udate) > 0) $m = ":i";
    }
    if ($m || date("H",$udate) > 0) list($h,$a) = array(" g","a");
    if ($dow) $w = "D ";else $w = "";
    return date("{$w}n/j$y $h$m$a",$udate);
  }

  function dbdate_show_long($thedate, $dow=true) {
    if ($thedate == "") return "";
    if ($thedate == ZERO_DATE) return "";
    $udate = strtotime($thedate);
    if (!$udate) return "";
    $sixmo = 182.5 * 24 * 60 * 60;
    if ($udate < time() - $sixmo || $udate > time() + $sixmo)
      $y = " Y";
    if (date("i",$udate) > 0) $m = ":i";
    if ($m || date("H",$udate) > 0) list($h,$a) = array(", g","a");
    if ($dow) $w = "l, ";else $w = "";
    return date("{$w}F j$y$h$m$a",$udate);
  }

  function valid_email($theAddy) {
    $regex = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";
    if (eregi($regex,$theAddy))
      return $theAddy;
    else
      return false;
  }

  function is_dbdate($thedate) {
    if ($thedate == "")
      return false;
    if ($thedate == ZERO_DATE)
      return false;
    if ($thedate == '1969-12-31 18:00:00')
      return false;
    return true;
  }

  function add_slashes(&$the_array) {
    if (FORMS_ESCAPED) {
      if (is_scalar($the_array))
        return $the_array;
      else
        return;
    }

    if (is_scalar($the_array))
      return addslashes(trim($the_array));
    if (is_array($the_array)) {
      foreach ($the_array as &$var) {
        if (is_scalar($var))
          $var = addslashes(trim($var));
      }
    }
  }

  function form_v(&$the_array) {
    if (is_scalar($the_array))
      return str_replace("'","&#039;",htmlspecialchars(trim($the_array)));
    if (is_array($the_array)) {
      foreach ($the_array as &$var) {
        if (is_scalar($var))
          $var = str_replace("'","&#039;",htmlspecialchars(trim($var)));
      }
    }
  }

  function swap_item($table,$id,$id2,$seq = 'seq',$prev = 'prev_seq',$member_of = 'member_of') {
    if (!($id > 0 && $id2 > 0 && $id <> $id2))
      return;

    do_query("
      update
        $table
      set
        $prev = $seq
      where
        id in ($id,$id2)");

    do_query("
      update
        $table a,
        $table b
      set
        a.$seq = b.$prev
      where
        a.id in ($id,$id2) and
        b.id in ($id,$id2) and
        a.id <> b.id");

    resequence_table($table,$seq,$member_of);
  }

  function new_display_order($table,$old_order,$disp_after,$seq = 'seq') {
    if ($old_order > 0) {
      do_query("
        update
          $table
        set
          $seq = $seq - 1
        where
          $seq > $old_order");
    }
    $the_order = do_query("
      select
        $seq as seq
      from
        $table
      where
        id = '$disp_after'");
    $the_order = $the_order->fetch_array();
    $the_order = $the_order["seq"];
    if ($the_order == '') $the_order = 0;
    do_query("
      update
        $table
      set
        $seq = $seq + 1
      where
        $seq > $the_order");
    $the_order++;
    return $the_order;
  }

  function new_member_of($table,$is_sub,$disp_order,$seq = 'seq',$member_of = 'member_of') {
    if ($is_sub <> 1)
      return 0;

    $sub_of = do_query("
        select
          id
        from
          $table
        where
          deleted = '".ZERO_DATE."' and
          $seq =
         (select
            max($seq) as the_order
          from
            $table
          where
            $member_of = 0 and
            $seq < $disp_order and
            deleted = '".ZERO_DATE."')");
    $sub_of = $sub_of->fetch_array();

    return $sub_of['id'];
  }

  function resequence_table($table,$seq = 'seq',$member_of = 'member_of') {
    do_query("
      set @counter = 0");

    do_query("
      update
        $table a,
       (select
          x.*,
          @counter:=@counter+1 as counter
        from
         (select
            s.id,s.$seq as main_order,0 as sub_order from $table s where $member_of = 0
          union
          select
            s.id,m.$seq,s.$seq from $table s join $table m on s.$member_of = m.id
          order by
            main_order,sub_order) x) b
      set
        a.$seq = b.counter
      where
        a.id = b.id");

  }

  function delete_item($table,$id) {
    do_query("
      update $table
        set
          deleted_by =
            case
              when deleted = '".ZERO_DATE."'
                then '{$_SESSION[SITE_PORT]['user_id']}'
                else deleted_by
            end,
          deleted =
            case
              when deleted <> '".ZERO_DATE."'
                then '".ZERO_DATE."'
                else now()
            end
      where
        id='$id'");
    retrieve_form_referer();
  }


  function get_status_opts($member_of,$thisid,$option_code) {
    if (!$thisid > 0) $thisid = 0;
    $status_codes = do_query("
      select
        id,
        name,
        is_default
      from
        status
      where
        member_of = '$member_of' and
        deleted = '".ZERO_DATE."'
      order by
        seq");
    $options[] = sprintf($option_code,0,'',' &mdash; Select &mdash; ');
    while ($stat = $status_codes->fetch_object()) {
      if ($stat->id == $thisid || ($thisid == 0 && $stat->is_default == 1))
        $slctd = "selected='selected'"; else $slctd = '';
      $options[] = sprintf($option_code,$stat->id,$slctd,$stat->name);
    }
    return $options;
  }

  function insert_status($member_of,$new_name){
    do_query("
      insert
        into status
          (name,
           seq,
           member_of,
           is_default,
           created_by,
           created)
         values
          ('$new_name',
           '9999',
           '$member_of',
           '0',
           '{$_SESSION[SITE_PORT]['user_id']}',
           now())");
    $new_id = $GLOBALS['db']->insert_id;

    resequence_table('status');

    return $new_id;
  }

  function get_opts($table,$field,$thisid,$option_code,$where = '',$order = '') {
    if (!$thisid > 0) $thisid = 0;
    if ($where <> '') $where = "and $where";
    if ($order == '')
      $order = $field;
    $rows = do_query("
      select
        id,
        $field
      from
        $table
      where
        deleted = '".ZERO_DATE."'
        $where
      order by
        $order");
    $options[] = sprintf($option_code,0,'',' &mdash; Select &mdash; ');
    while ($row = $rows->fetch_object()) {
      if ($row->id == $thisid)
        $slctd = "selected='selected'"; else $slctd = '';
      $options[] = sprintf($option_code,$row->id,$slctd,$row->$field);
    }
    return $options;
  }
  
  function get_members_of($id) {
    return do_query("
      select
        *
      from
        status
      where
        member_of = '$id' and
        deleted = '".ZERO_DATE."'
      order by
        seq");
  }

  function retrieve_valid_emails($emails,$default = SITE_EMAIL_TO) {
    if ($emails != '') {
      $emails = explode(',',$emails);
      foreach ($emails as $email) {
        if (valid_email($email))
          $good_email[] = $email;
      }
    }
    if (!isset($good_email))
      $good_email[] = $default;
    return $good_email;
  }

  function printHeader() {
    global $pageInfo;
    if ($pageInfo['header_photo'] != "")
      echo "<img class='first' src='{$pageInfo['header_photo']}' alt='' />";
    if ($pageInfo['header'] != "")
      echo "<h1 class='first'>{$pageInfo['header']}</h1>";
    if ($pageInfo['sub_head'] != "")
      echo "<h2 class='first'>".nl2br($pageInfo['sub_head'])."</h1>";
  }

  function printHeadSlashSubhead() {
    global $pageInfo;
    if ($pageInfo['file_name'] == '.')
      echo ''; // no page header on home page
    else if ($pageInfo['sub_id'] > 0)
      echo "
        <h1 class='sub'><span class='main'>{$pageInfo['section_title']} / </span>{$pageInfo['page_title']}</h1>\r";
    else
      echo "
        <h1 class='main'>{$pageInfo['page_title']}</h1>\r";
    if ($pageInfo['header'] != "")
      echo "<h1 class='first'>{$pageInfo['header']}</h1>\r";
  }

  function printContent($editor = '1') {
    if ($GLOBALS['_URL'][2] == "edit")
      printEditPage();
    else {
      processPlugins($GLOBALS['pageInfo']['content']);
      printEditButton();
    }
  }
  
  function printEditPage() {
    $GLOBALS['pageInfo']['scripts'] .= "\n/xtool/ckeditor/ckeditor.js";
?>
      <form action='/admin/page/update/<?=$GLOBALS['pageInfo']['id'];?>' method='post' class='wysiwyg'>
        <a name='wysiwyg_block' id='wysiwyg_block'></a>
        <textarea name='content_edit' id='content_edit' tabindex='1'><?=htmlspecialchars($GLOBALS['pageInfo']['content']);?></textarea>
        <script type='text/javascript'>
          CKEDITOR.replace('content_edit',
            {
              customConfig : '/admin/ckeditor/config.js',
              toolbar : 'Page'
            });
        </script>
        <br />
        <input type='hidden' name='action_step' value='process' />
        <input type='submit' name='enter' tabindex='2' value='Save page' />
        <input type='reset'  name='reset' tabindex='3' value='Cancel' tabindex='<?=$tbi++;?>'
              onclick='window.location = "<?=SITE_HTTP."/".$GLOBALS['_URL'][1];?>/"' />
      </form>
<?
  }

  function printEditButton() {
    return;
    if (checkPermissions($GLOBALS['pageInfo']['id']) &&
        $GLOBALS['pageInfo']['file_name'] <> "404-not-found" &&
        $GLOBALS['_URL']['1'] <> 'admin') {
      $page_addr = ($GLOBALS['_URL'][1] == '.') ? "home_page" : $GLOBALS['_URL'][1];
?>
      <br />
      <div class='edit_page_button'>
        <input type='submit' name='edit' value='Edit page content'
              onclick='window.location = "<?=SITE_HTTP."/".$page_addr;?>/edit#wysiwyg_block"' />
        <input type='submit'  name='edit_all' value='Edit full page'
              onclick='window.location = "<?=SITE_HTTP;?>/admin/page/edit/<?=$GLOBALS['pageInfo']['id'];?>"' />

      </div>
<?
    }
  }

  function printEditLinks() {
    if (checkPermissions($GLOBALS['pageInfo']['id']) &&
        $GLOBALS['pageInfo']['file_name'] <> "404-not-found" &&
        $GLOBALS['_URL']['1'] <> 'admin') {
      $page_addr = ($GLOBALS['_URL'][1] == '.') ? "home_page" : $GLOBALS['_URL'][1];
?>
        <a href='/<?=$page_addr;?>/edit'>Edit</a>
        <a href='/admin/page/edit/<?=$GLOBALS['pageInfo']['id'];?>'>Config</a>

<?
    }
  }

  function adminMenu() {
    if($_SESSION[SITE_PORT]['user_type'] && $_SESSION[SITE_PORT]['user_type'] > 0) {
      require(ABSPATH . 'admin/admin-menu-top.php');
    }
  }

  function processPlugins($content) {
    global $_URL, $_TAG;

    $content = explode("&lt;",$content);
    if (sizeof($content) < 2) {
      echo $content[0];
      return;
    }

    $mresult = do_query("
      select
        *
      from
        plugin
      where
        active = '1' and
        deleted = '".ZERO_DATE."'
      order by
        length(tag) desc");
    while($mrow = $mresult->fetch_array())
      $avail_modules[] = $mrow;
    $mresult->close;

    $index = 0;
    foreach($content as $this_piece) {
      $found_tag = false;
      $plugin_loc = "";
      foreach($avail_modules as $module) {
        if(substr($this_piece,0,strlen($module['tag'])) == $module['tag']) {
          $found_tag = true;
          $plugin_loc = $module['plugin_loc'];
          break;
        }
      }
      if($found_tag) {
        $sub_content = explode("&gt;",$this_piece);
        $attributes = substr($sub_content[0],strlen($module['tag']));
        $attributes = str_replace("&quot;","\"",$attributes);
        $tok = strtok($attributes, '="');

        $get_name = true;
        $attribute_name = "";
        while ($tok) {
          if($get_name) {
            $attribute_name = trim($tok);
            if (substr($attribute_name,0,6) == '<br />')
              $attribute_name = trim(substr($attribute_name,7));
            $get_name = false;
          } else {
            $_TAG[$attribute_name] = trim($tok);
            $get_name = true;
          }
          $tok = strtok('="');
        }
        if ($plugin_loc <> "")
          require(ABSPATH . $plugin_loc);
        echo "$sub_content[1]";
      } else {
        if($index != 0) echo "&lt;";
        echo $this_piece;
      }
      $index++;
    }
  }

  function checkPermissions($page_id) {
    if ($page_id == 1)
      return false;
    if (in_array($_SESSION[SITE_PORT]['user_type'],array('1','2')))
      return true;
    if ($_SESSION[SITE_PORT]['user_type'] == 3 && in_array($page_id,explode(',',$_SESSION[SITE_PORT]['user_pages'])))
      return true;
    else
      return false;
  }

  function dump_string($string) {
    for ($x = 0;$x < strlen($string); $x++)
      list($dump[0][],$dump[1][],$dump[2][]) = array($x+1,substr($string,$x,1),ord(substr($string,$x,1)));
    echo "
      <table>";
    $row_start = "<tr><td>";
    $item_delim = "</td><td>";
    $row_end = "</td></tr>";
    foreach($dump as $d)
      $row[] = implode($item_delim,$d);
    foreach($row as $r)
      echo $row_start.$r.$row_end;
    echo "
      </table>";
  }
  
  function nl2array($string) {
    $new_string = nl2br($string);
    $new_string = str_replace(chr(13),'',$new_string);
    $new_string = str_replace(chr(10),'',$new_string);
    return(explode("<br />",$new_string));
  }
  
  function hex2ascii($string) {
    $p = '';
    for ($i=0; $i < strlen($string); $i=$i+2)
      $p .= chr(hexdec(substr($string, $i, 2)));
    return $p;
  }

  function date_block($item,$flds = array('created','updated','deleted')) {
    $date_code = "
          <li>
            <label>
              %s:
            </label>
            %s %s
          </li>";
    foreach ($flds as $fld) {
      if (is_dbdate($item[$fld])) {
        $by_name_fld = $fld."_by_name";
        $byline = ($item[$by_name_fld] <> '') ? "by ".$item[$by_name_fld] : '';
        $date_data[] = sprintf($date_code,ucwords(str_replace('_',' ',$fld)),dbdate_show($item[$fld],true),$byline);
      }
    }
    if (!isset($date_data)) $date_data[] = sprintf($date_code,'&nbsp;','&nbsp;','&nbsp;');
    return implode("\n",$date_data);
  }

  function sprintf4($str,$vars) {
    return str_replace(array_keys($vars),array_values($vars),$str);
  }

  function input_field($fldname,$tabindex=0,$fldlabel='',$fldtype='text',$value='',
                       $width='',$morestyle='',$labelpos='before',$script='') {
    $label_code = "
            <label for='%fldname%'>%fldlabel%</label>";
    $input_code = "
            <input id='%fldname%' name='%fldname%' type='%fldtype%' %tabindex%
              %script% %stylebase%%width%%morestyle%%styleclose% value='%value%' />";
    $textarea_code = "
            <textarea id='%fldname%' name='%fldname%' %tabindex%
              %script% %stylebase%%width%%morestyle%%styleclose%'>%value%</textarea>";
    if ($fldlabel <> '' && $labelpos == 'before')
      $outpt .= sprintf4($label_code,
                array('%fldname%'  => $fldname,
                      '%fldlabel%' => $fldlabel));
    $tabindex     = ($tabindex > 0)    ? "tabindex='$tabindex'" : '';
    $morestyle    = ($morestyle <> '') ? ";$morestyle" : '';
    $stylebase    = ($width <> '')     ? "style='width:" : '';
    $styleclose   = ($width <> '')     ? "'" : '';
    if ($fldtype == 'textarea')
      $the_code   = $textarea_code;
    else
      $the_code   = $input_code;
    $outpt       .= sprintf4($the_code,
              array('%fldname%'   => $fldname,
                    '%fldtype%'   => $fldtype,
                    '%tabindex%'  => $tabindex,
                    '%width%'     => $width,
                    '%morestyle%' => $morestyle,
                    '%stylebase%' => $stylebase,
                    '%styleclose%'=> $styleclose,
                    '%value%'     => $value,
                    '%script%'    => $script));
    if ($fldlabel <> '' && $labelpos == 'after')
      $outpt .= sprintf4($label_code,
                array('%fldname%'  => $fldname,
                      '%fldlabel%' => $fldlabel));
    return $outpt."\n";
  }

  function site_login_link() {

    $public_code = "
      <a href='/admin/'>PROPERTY MANAGER LOGIN <img alt='' src='/site/image/layout/link-logo.png' /></a>";
    $logged_in_code = "
      <a href='/admin/logout'>(logout) <img alt='' src='/site/image/layout/link-logo.png' /></a>
      <a href='/admin/'>Logged in as %s</a>";

    if (isset($_SESSION[SITE_PORT]['user_id']))
      printf($logged_in_code,$_SESSION[SITE_PORT]['user_name']);
    else
      echo $public_code;

  }

  function set_form_referer() {
    if ($_SERVER['HTTP_REFERER'] <> '') {
      if ($_SESSION['form_referer'] <> SITE_HTTP.$_SERVER['REQUEST_URI'])
      $_SESSION['form_referer'] = $_SERVER['HTTP_REFERER'];
    } else {
      if (isset($_SESSION['form_referer']))
        unset($_SESSION['form_referer']);
    }
  }

  function retrieve_form_referer() {
    if (isset($_SESSION['form_referer']) <> '') {
      $GLOBALS['next_page'] = $_SESSION['form_referer'];
      unset($_SESSION['form_referer']);
    }
  }
                                               /* "69.24.165.115" chamber */
  function sessionInfo() {
    if (in_array($_SERVER['REMOTE_ADDR'],array("173.17.120.233x","127.0.0.1x"))) {
      return "
        <div id='sess_info'>
          s: ".session_id()."<br/>
          u: ".implode("/",$_SESSION[SITE_PORT])."
        </div>";
    } 

  }
  
  function Check_splash_page() {
    if (defined('COUNTDOWN_UNTIL') && defined('COUNTDOWN_MODULE') && defined('COUNTDOWN_SERVER')) {
      if ((date(DB_DATE)) < COUNTDOWN_UNTIL) {
        if (in_array($_SERVER['SERVER_NAME'],array(COUNTDOWN_SERVER,'www.'.COUNTDOWN_SERVER))) {
        require(ABSPATH.COUNTDOWN_MODULE);
        exit;
        }
      }
    }
  }

?>