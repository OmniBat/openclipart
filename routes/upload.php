<?php

$app->get("/upload", function() use($app){
  return $app->render('upload');
});

$app->post("/upload", function() use($app){
  error_log('post upload');
  if(!$app->is_logged()) return $app->redirect('/');
  $files = $_FILES['files'];
  error_log('file: ' . $_FILES['files']['name'][0]);
  $userid = $app->config->userid;
  error_log("user id : $userid");
  foreach($files['name'] as $ind => $filename){
    error_log('creating clipart');
    $app->clipart_create($userid, array(
      'filename' => $filename
      , 'title' => $_POST['title'][$ind]
      , 'description' => $_POST['description'][$ind]
      , 'author' => $_POST['author'][$ind]
      , 'filesize' => $files['size'][$ind]
      , 'tmp_name' => $files['tmp_name'][$ind]
      , 'type' => $files['type'][$ind]
    ));
    error_log('before insert id');
    $clipid = $app->db->insert_id();
    error_log('after insert id');
    $tags = preg_split("/[\s]*[,][\s]*/", $_POST['tags'][$ind]);
    $tags = array_map(function($tag){
      return preg_replace("/[^a-zA-Z0-9]/", "", $tag);
    }, $tags);
    var_dump($tags);
    $app->set_clipart_tags($clipid, $tags);
  }
});

?>