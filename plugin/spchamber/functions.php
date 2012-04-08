<?

  function print_one_business($lines,$busns) {

    $thecode = array('business_name'=>"
      <h3>%s</h3>",
                  'business_name_2_nl2br'=>"
      %s<br />",
                  'contact_name_role'=>"
      %s<br />",
                  'address_nl2br'=>"
      %s<br />",
                  'city_state_zip'=>"
      %s %s %s<br />",
                  'phone_1_and_desc'=>"
      %s %s<br />",
                  'phone_2_and_desc'=>"
      %s %s<br />",
                  'phone_fax_and_desc'=>"
      %s %s<br />",
                  'email'=>"
      <a href='mailto:%s'>%s</a><br />",
                  'url'=>"
      <a href='%s'>%s</a><br />",
                  'blurb_nl2br'=>"
      <div class='blurb'>%s</div>");
        
    if (in_array($_SESSION[SITE_PORT]['user_type'],array('1','2'))
       || in_array(13,explode(',',$_SESSION[SITE_PORT]['modules'])))
      list($editable,$onclick) = array(" editable","
         onclick=\"location.href='".SITE_HTTP."/admin/business/edit/{$busns->id}';\"");

    $wrapper = "
    <div class='business_lstg$editable' $onclick>%s
    </div>\n";

    $busns_lines = 0;
    foreach ($thecode as $field=>$code) {
      if (isset($busns->$field) and $busns->$field <> '') {
        $print[] = sprintf($code,$busns->$field);
        $busns_lines = $busns_lines + 1 + floor(strlen($busns->$field/60));
      } else {
        $fld_seg = explode('_',$field);
        $fld_suff = array_pop($fld_seg);
        $fld_name = implode('_',$fld_seg);
        if ($fld_suff == 'nl2br' && isset($busns->$fld_name) && $busns->$fld_name <> '') {
          $prt_lines = nl2array($busns->$fld_name);
          foreach ($prt_lines as $prt_line)
            $busns_lines += ceil(strlen($prt_line) / 60);
          $print[] = sprintf($code,implode('<br />',$prt_lines));
        } else if ($fld_seg[0] == 'phone') {
          if ($fld_seg[1] == 'fax' && $busns->phone_fax <> '') {
            $desc = 'fax';
            $phone_fld = 'phone_fax';
          } else {
            array_pop($fld_seg);
            $phone_fld = implode('_',$fld_seg);
            $phone_dsc = $phone_fld."_desc";
            if (isset($busns->$phone_dsc) && $busns->$phone_dsc <> '')
              $desc = $busns->$phone_dsc;
          }
          if (isset($desc))
            $desc = "($desc)";
          if ($busns->$phone_fld <> '') {
            $print[] = sprintf($code,$busns->$phone_fld,$desc);
            $busns_lines++;
          }
        } else if ($field == 'city_state_zip') {
          if ($busns->city <> '' && $busns->state <> '' && $busns->zip <> '') {
            $print[] = sprintf($code,$busns->city,$busns->state,$busns->zip);
            $busns_lines++;
          }
        } else if ($field == 'contact_name_role') {
          $name = array();
          if ($busns->contact_name_first <> '')
            $name[] = $busns->contact_name_first;
          if ($busns->contact_name_last <> '')
            $name[] = $busns->contact_name_last;
          if (count($name) > 0)
            $pName = implode(' ',$name);
          if ($busns->contact_role <> '') {
            if ($pName <> '')
              $pName .= ", {$busns->contact_role}";
            else
              $pName = $busns->contact_role;
          }
          if ($pName <> '') {
            $print[] = sprintf($code,$pName);
            $busns_lines++;
          }
        } else if ($field == 'email') {
          $email = valid_email($busns->email_public);
          if ($email <> '') {
            $print[] = sprintf($code,$email,$email);
            $busns_lines++;
          }
        } else if ($field == 'url') {
          if ($busns->website <> '') {
            $site = $busns->website;
            $link = (substr($site,0,7) == "http://") ? $site : "http://$site";
            $site = str_replace(array('http://www.','http://'),array('',''),$link);
            $fb = str_replace("facebook.com/pages/","Facebook: ",$site);
            if ($site <> $fb)
              $site = str_replace('-',' ',array_shift(explode('/',$fb)));
            $site = (strlen($site) < 36) ? $site : substr($site,0,34)."...";
            $print[] = sprintf($code,$link,$site);
            $busns_lines++;
          }
        }
      }
    }
    //$busns_lines++;
    $lines = $lines + $busns_lines;
    return array($lines,array('lines'=>$busns_lines,'print'=>sprintf($wrapper,implode('',$print))));
    
  }

  function insert_site_content($url,$div) {
    list($title,$content_html,$content_plain) = retrieve_page_content($url,$div);
    do_query("
      insert into
        site_content
         (url,
          title,
          content_html,
          content_plain,
          created,
          created_by)
        values
         ('/$url',
          '$title',
          '$content_html',
          '$content_plain',
          now(),
          '{$_SESSION[SITE_PORT]['user_id']}')");
  }

  function update_site_content($url,$div,$oldurl) {
    list($title,$content_html,$content_plain) = retrieve_page_content($url,$div);
    do_query("
      update
        site_content
      set
        url = '/$url',
        title = '$title',
        content_html = '$content_html',
        content_plain = '$content_plain',
        updates = updates + 1,
        updated = now(),
        updated_by = '{$_SESSION[SITE_PORT]['user_id']}'
      where
        url = '/$oldurl'");
    if ($GLOBALS['db']->affected_rows == 0)
      insert_site_content($url,$div);
  }

  function delete_page_site_content($id) {
    $pages = do_query("
      select
        status,
        file_name
      from
        page
      where
        id = '$id'");
    if ($pages->num_rows == 1) {
      $page = $pages->fetch_object();
      if ($page->status == 'active')
        delete_site_content($page->file_name);
    }
  }

  function delete_site_content($url) {
    do_query("
      delete from
        site_content
      where
        url = '/$url'");
  }

  function retrieve_page_content ($url,$div) {
    require_once(ABSPATH . 'xtool/simple_html_dom.php');
    $the_page = file_get_html(SITE_HTTP."/$url");
    $text = trim(array_shift($the_page->find("div#$div"))->plaintext);
    $text = str_replace(chr(9),' ',$text);
    $text = str_replace('&nbsp;',' ',$text);
    while (strpos($text,'  ') > 0)
      $text = str_replace('  ',' ',$text);
    $title = str_replace(TITLE_SUFFIX,'',addslashes(array_shift($the_page->find('title'))->innertext));
    $content_html = addslashes(array_shift($the_page->find("div#$div"))->innertext);
    $content_plain = addslashes($text);
    return array($title,$content_html,$content_plain);
  }
  
  function retrieve_non_pub_cats() {
    $cats = do_query("
      select
        group_concat(id separator ',') as cats
      from
        status
      where
        is_closed = 1");
    $cat = $cats->fetch_object();
    return explode(',',$cat->cats);
  }
  
  function retrieve_other_pages($cats) {
    $other_pages = do_query("
        select
          on_pages
        from
          status
        where
          id in (".implode(',',$cats).") and
          on_pages <> ''");
    $pages = array();
    while ($other = $other_pages->fetch_object()) {
      foreach(explode(',',$other->on_pages) as $page) {
        if (!in_array($page,$pages))
          $pages[] = $page;
      }
    }
    return $pages;
  }

?>