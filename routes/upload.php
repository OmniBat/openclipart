<?php

$app->get("/upload", function() use($app){
  return $app->render('upload');
});

?>