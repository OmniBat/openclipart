<?php

$app->get("/upload", function() use($app){
  return $app->render('upload');
});

$app->post("/upload", function() use($app){
  if(!$app->is_logged()) return $app->redirect('/');
  var_dump($_FILES);
  var_dump($_POST);
});

?>