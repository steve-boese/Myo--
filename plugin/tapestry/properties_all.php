<?
  $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/properties.css";

  global $_URL;
  
  switch ($_URL[2]) {
    case 'all':
      all_properties();
      break;
    case '':
      properties_summary();
      break;
    default:
      one_property($_URL[2]);
  }

  function all_properties() {
    $properties = retrieve_all();

    $code_loc = "
        <h2 class='loc_cat'>
          %s
        </h2>";

    while ($item = $properties->fetch_object()) {
      if ($item->cat <> $curr_cat) {
        printf($code_loc,$item->category);
        $curr_cat = $item->cat;
      }
      echo one_item($item);
    }
  }

  function properties_summary() {
    $properties = retrieve_all();

    $start_code = "\r\r
    <table id='prop_sum'>
      <tr>";
    $start_cell = "
        <td>";
    $end_cell = "
        </td>";
    $row_break = "
      </tr>
      <tr>";
    $end_code = "
      </tr>
    </table>";
    
    $code_loc = "
          <h2 class='loc_cat'>
            %s
          </h2>";

    while ($item = $properties->fetch_object()) {
      if ($item->cat <> $curr_cat) {
        $cats[] = $item->cat;
        $cat[$item->cat]['header'] = sprintf($code_loc,$item->category);
        $curr_cat = $item->cat;
      }
      $cat[$item->cat]['content'][] = one_item($item,true);
    }
    
    echo $start_code.$start_cell;
    foreach ($cats as $c) {
      switch ($c) {
        case 4:
          echo $end_cell.$start_cell;
          break;
        case 7:
          echo $end_cell.$row_break.$start_cell;
      }
      echo $cat[$c]['header'];
      foreach ($cat[$c]['content'] as $ct)
        echo $ct;
    }
    echo $end_cell.$end_code;
  }

  function one_property($id) {
    $properties = retrieve_all($id);

    $item = $properties->fetch_object();
    echo one_item($item);
    $GLOBALS['pageInfo']['title'] = $item->business_name . TITLE_SUFFIX;
    
  }

  function retrieve_all($id = null) {
    if ($id > 0)
      $where = "
        p.id = '$id' and";
    return do_query("
      select
        t.status_id as cat,
        s.name as category,
        p.*
      from
        property p
      join
        property_type t
          on p.id = t.property_id
      join
        status s
          on t.status_id = s.id
      where $where
        s.member_of = 1
      order by
        s.seq,
        p.business_name");
  }

  function one_item($item,$summary = false) {
    $code_image = "
        <img alt='' src='%s' />";
    $code_name = "
        <h3><a href='/properties/%s'>%s</a></h3>";
    $code_addr = "
        <div class='address_block'>
          %s
        </div>";
    $code_graf = "
        <p>
          %s
        </p>";
    $code_list = "
        <ul>%s
        </ul>";
    $code_item = "
          <li>%s</li>";

    if ($item->photo <> '')
      $code .= sprintf($code_image,$item->photo);
    $code   .= sprintf($code_name ,$item->id,$item->business_name);
    
    if ($item->address <> '')
      $addr .= nl2br($item->address)."<br />";
    if ($item->city <> '')
      $addr   .= sprintf("%s, %s %s<br />",$item->city,$item->state,$item->zip);
    if ($item->directions <> '')
      $addr   .= nl2br($item->directions)."<br />";
    if ($item->phone <> '')
      $addr .= "Phone: {$item->phone}<br />";
    if ($item->fax <> '')
      $addr .= "Fax: {$item->fax}<br />";
    if (!$summary)
      $email_label = "Email: ";
    if (valid_email($item->email))
      $addr .= sprintf("$email_label<a href='mailto:%s'>%s</a>",$item->email,$item->email);
    $code   .= sprintf($code_addr,$addr);

    $fields = ($summary) ? array() : array(
              'designed_for'=>array('type'=>'plain'),
              'location'    =>array('type'=>'plain','head'=>'<strong>Location:</strong> '),
              'unit_types'  =>array('type'=>'list' ),
              'tenants'     =>array('type'=>'plain','head'=>'<strong>Tenants:</strong> '),
              'space_avail' =>array('type'=>'plain','head'=>'<strong>Space available:</strong> '),
              'amenities'   =>array('type'=>'plain','head'=>'<strong>Amenities:</strong> '),
              'activities'  =>array('type'=>'plain','head'=>'<strong>Activities:</strong> '),
              'public_trans'=>array('type'=>'plain','head'=>'<strong>Public transportation:</strong> '),
              'nearby'      =>array('type'=>'plain','head'=>'<strong>Nearby:</strong> '),
              'map'         =>array('type'=>'map'  ),
              'section_8'   =>array('type'=>'plain'),
              'contact_info'=>array('type'=>'plain'));
              
    foreach ($fields as $name=>$def) {
      if (trim($item->$name) <> '' || $def['type'] == 'map') {
        switch ($def['type']) {
          case 'list':
            $units = explode('<br />',nl2br(trim($item->$name)));
            foreach ($units as &$u)
              $u = sprintf($code_item,$u);
            $code .= sprintf($code_list,implode('',$units));
            break;
          case 'map':
            $terms = str_replace(' ','+',"{$item->address} {$item->city} {$item->state} {$item->zip}");
            $code .= sprintf($code_graf,
                     sprintf("<a class='loc' href='http://maps.google.com/maps?q=%s'>Location Map</a>",$terms));
            break;
          default:
            $code .= sprintf($code_graf,$def['head'].nl2br($item->$name));
        }
      }
    }

    if ($summary)
      $script = " onclick=\"location.href='".SITE_HTTP."/properties/{$item->id}';\"";
    
    $code  = str_replace('&','&amp;',$code);
    return "\r
      <div class='one_prop'$script>$code
      </div>\r";
  }

?>