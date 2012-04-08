<?php

  $del = do_query("
    select
      *
    from
      mailing_list
    where
      (deleted   < date_sub(now(),INTERVAL 7 DAY) and deleted   > 0) or
      (opted_out < date_sub(now(),INTERVAL 7 DAY) and opted_out > 0)
    order by
      email,
      group_name,
      name");

  echo "Deleting ".mysql_num_rows($del)." entries";

  $del = do_query("
    delete
    from
      mailing_list
    where
      (deleted   < date_sub(now(),INTERVAL 7 DAY) and deleted   > 0) or
      (opted_out < date_sub(now(),INTERVAL 7 DAY) and opted_out > 0)");

?>