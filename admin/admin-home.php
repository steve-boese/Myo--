<?

  $pgresult = do_query("
    select
      p.*,
      t.stylesheets,
      t.scripts,
      t.favicon
    from
      page p
    left join
      template t
        on t.name = 'Admin'
    where
      p.file_name = 'admin-home'");
      
  $GLOBALS['pageInfo'] = $pgresult->fetch_array();
  
  printHeader();
  printContent();
  
  $pgresult->close();

?>