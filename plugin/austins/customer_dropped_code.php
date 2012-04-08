    /*
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
    $templates = do_query("select * from template where id > 1 order by name");
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

    $date_code = "
          <li>
            <label>
              %s:
            </label>
            %s
          </li>";
    $date_block = array(
        "created"    => "Created",
        "updated"    => "Updated",
        "deleted"    => "Deleted");
    foreach ($date_block as $fld=>$label) {
      if (is_dbdate($item[$fld]))
        $date_data .= sprintf($date_code,$label,dbdate_show($item[$fld],true));
    }
    if (!isset($date_data)) $date_data = sprintf($date_code,"&nbsp;","&nbsp;");

    $this_page_content = htmlspecialchars($item['content']);
    */
