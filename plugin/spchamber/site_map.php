<?php

  $file_name = $GLOBALS['_URL'][1] or '.';

  $menu_items = retrieve_sitemap();

  $order[] = 1;
  $menu[1] = array('text'=>'HOME','link'=>'.');
  while ($item = $menu_items->fetch_object()) {
    if ($item->indent == 1) {
      $menu[$item->member_of]['subs'][] = array('text'=>$item->menu_name,'link'=>$item->file_name);
      if ($file_name == $item->file_name)
        $menu[$item->member_of]['active'] = true;
    } else {
      $order[] = $item->id;
      $menu[$item->id]['text'] = ($item->menu_name <> '') ? $item->menu_name : $item->title;
      $menu[$item->id]['link'] = $item->file_name;
      if ($file_name == $item->file_name)
        $menu[$item->id]['active'] = true;
    }
  }

  $main_code = "
        <ul id='site_map'>%s
        </ul>\r";
  $main_link = "
          <li>%d. <a href='/%s'>%s</a>%s
          </li>";
  $subs_code = "
            <ul>%s
            </ul>";
  $subs_link = "
              <li>%d.<a href='/%s'>%s</a></li>";

  foreach ($order as $ord) {
    $main = ++$seq;
    $thisone = '';
    if (is_array($menu[$ord]['subs'])) {
      foreach($menu[$ord]['subs'] as $lnk)
        $thisone .= sprintf($subs_link,++$seq,$lnk['link'],$lnk['text']);
      $thisone = sprintf($subs_code,$thisone);
    }
    $code .= sprintf($main_link,$main,$menu[$ord]['link'],$menu[$ord]['text'],$thisone);
  }
  printf($main_code,$code);



  function retrieve_sitemap() {

    return do_query("
    select
      p.display_order,
      0 as sub_order,
      0 as indent,
      p.menu_name,
      p.file_name,
      p.title,
      p.id,
      0 as member_of
    from
      page p
    where
      file_name <> '.' and
      p.status = 'active' and
      p.sub_id = 0 and
      p.deleted = 0

    union

    select
      p.display_order,
      s.display_order as sub_order,
      1 as indent,
      s.menu_name,
      s.file_name,
      s.title,
      s.id,
      s.sub_id as member_of
    from
      page s
    join
      page p
        on s.sub_id = p.id
    where
      s.sub_id > 0 and
      s.status = 'active' and
      p.status = 'active' and
      s.deleted = 0 and
      p.deleted = 0

    order by
      display_order,
      sub_order");

  }

?>