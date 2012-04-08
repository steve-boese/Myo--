<?

  if ($_SERVER['PHP_SELF'] <> '/index.php') exit;
  
  if (!isset($_SESSION[SITE_PORT]['user_id'])) return;
  
  $GLOBALS['pageInfo']['stylesheets'] .= "\n/site/style/admin-menu-top.css";

  adm_top_menu_start();
  
  $where   = ($_SESSION[SITE_PORT]['user_type'] > 1) ? 'master_only = 0 and ' : '';
  $modules = $_SESSION[SITE_PORT]['user_modules'] or '0';
  $where  .= ($_SESSION[SITE_PORT]['user_type'] == 3) ? "id in ($modules) and" : '';

  $menu_items = do_query("
    select
      *
    from
      plugin
    where $where
      active = '1' and
      admin_loc <> '' and
      deleted = 0
    order by
      seq");
      
  while ($item = $menu_items->fetch_object())
    adm_top_menu_item($item);

  adm_top_menu_end();
  
  $menu_items->close();


  function adm_top_menu_start() {
?>

<table class='admin_menu'>
  <tr>
    <td class='menu_cell'>
      <!-- a href='/admin/'>Admin Home</a -->
      <?=printEditLinks();?>
<?
  }

  function adm_top_menu_item($item) {

    $code = "
      <a href='%s'%s>%s</a>";

    if ($item->admin_by_link == '1')
      printf($code,$item->admin_loc," rel='external'",$item->name);
    else
      printf($code,"/admin/{$item->tag}/",'',$item->name);

  }

  function adm_top_menu_end() {
?>
      <a href='/admin/logout/'>Logout</a>
    </td>
  </tr>
</table>
<?
  }

?>