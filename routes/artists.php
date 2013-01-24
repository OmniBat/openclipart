<?php

$app->get('/artists', function() use($app){
  return $app->render('artists');
});

?>