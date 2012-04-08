<?

  if ($_SERVER['PHP_SELF'] <> '/admin/index.php') return;
  if (!isset($_SESSION[SITE_PORT]['user_type'])) return;

  adm_top_menu_start();

  $where   = ($_SESSION[SITE_PORT]['user_type'] > 1) ? 'master_only = 0 and ' : '';
  $modules = (strlen($_SESSION[SITE_PORT]['user_modules']) > 0) ? $_SESSION[SITE_PORT]['user_modules'] : '0';
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


  function adm_top_menu_start() {
?>
          <ul>
            <li><a href='/'>Site Home</a></li>
            <li><a href='/admin/'>Admin Home</a></li>
<?
  }

  function adm_top_menu_item($item) {
    $code = "
            <li><a href='%s'%s>%s</a></li>";
            
    if ($item->admin_by_link == '1')
      printf($code,$item->admin_loc," rel='external'",$item->name);
    else
      printf($code,"/admin/{$item->tag}/",'',$item->name);
            
  }

  function adm_top_menu_end() {
?>
            <li><a href='<?=CMS_SUPPORT_LINK;?>'>Help &amp; Support</a></li>
            <li><a href='/admin/logout/'>Logout</a></li>
          </ul>
<?
  }

?>