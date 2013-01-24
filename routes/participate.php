<?php

$app->get('/participate', function() use($app){
  return $app->render('participate');
});

?>