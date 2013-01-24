<?php

$app->get("/upload", function() use($app){
  return $app->render('upload');
});

$app->post("/upload", function() use($app){
  if(!$app->is_logged()) return $app->redirect('/');
  $files = $_FILES['files'];
  foreach($files['name'] as $ind => $filename){
    $app->clipart_create($app->config->userid, array(
      'filename' => $filename
      , 'title' => $_POST['title'][$ind]
      , 'description' => $_POST['description'][$ind]
      , 'filesize' => $files['size'][$ind]
      , 'tmp_name' => $files['tmp_name'][$ind]
      , 'type' => $files['type'][$ind]
    ));
  }
});

?>