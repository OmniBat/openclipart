<?php

$app->get('/librarians', function() use($app){
  return $app->render('librarians', array(
    'librarians' => $app->get_users_by_group('librarian')
    , 'developers' => $app->get_users_by_group('admin')
  ));
});

?>
