<?php

  $file_name = $GLOBALS['_URL'][1] or '.';

  $menu_items = retrieve_menu();

  menu_header();

  $indented = false;
  $closer = "";
  $i = 0;
  while ($item = $menu_items->fetch_object()) {
    $selected    = ($file_name == $item->file_name) ? true:false;
    $link_class  = ($selected)? 'selected':'';
    $conn_class  = (($i == 0) ? 'left':'middle').
                   ($prev_selected ? ' right_on':'').
                   ($selected ? ' left_on':'');

    $indented    = ($indented && $item->indent == 0) ? sub_menu_close() : $indented;
    $closer      = ($item->indent == 0) ? menu_item($item,$link_class,$conn_class,$closer) : $closer;
    $indented    = ($item->indent == 1) ? sub_menu_item($item,$link_class,$indented) : $indented;

    $prev_selected = $selected;
    $i++;
  }

  menu_footer($prev_selected,$indented);


  function retrieve_menu() {
    return do_query("
    select
      p.display_order,
      0 as sub_order,
      0 as indent,
      p.menu_name,
      p.file_name
    from
      page p
    where
      p.sub_id = 0 and
      p.display_link = '1' and
      p.deleted = 0

    union

    select
      p.display_order,
      s.display_order as sub_order,
      1 as indent,
      s.menu_name,
      s.file_name
    from
      page s
    join
      page p
        on s.sub_id = p.id
    where
      s.sub_id > 0 and
      s.display_link = '1' and
      p.display_link = '1' and
      s.deleted = 0 and
      p.deleted = 0

    order by
      display_order,
      sub_order");

  }

  function menu_header() {
?>

            <ul>
<?
  }
  
  function menu_item($item,$link_class,$conn_class,$closer) {
    if ($link_class)
      $link_class = "class='$link_class'";
    echo $closer;
?>
              <li>
                <a href='/<?=$item->file_name;?>' <?=$link_class;?>><?=htmlentities($item->menu_name);?></a>
<?
    return "
              </li>\n";
  }

  function sub_menu_item($item,$link_class,$indented) {
    if ($link_class)
      $link_class = "class='$link_class'";
    if (!$indented) {
?>
                <ul>
<?
    }
?>
                  <li>
                    <a href='/<?=$item->file_name;?>' <?=$link_class;?>><?=htmlentities($item->menu_name);?></a>
                  </li>
<?
    return true;
  }

  function sub_menu_close() {
?>
                </ul>
<?
    return false;
  }

  function menu_footer($prev_selected,$indented) {
    if ($indented) {
?>
                </ul>
<?
    }
?>
              </li>
            </ul>

<?
  }

?>
