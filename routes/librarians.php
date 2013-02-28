<?php

$app->get('/librarians', function() use($app){
  return $app->render('librarians');
});

?>
