<?php

$app->get("/upload", function() use($app){
  return $app->render('upload');
});

$app->post("/upload", function() use($app){
  if(!$app->is_logged()) return $app->redirect('/');
  $files = $_FILES['files'];
  $userid = $app->config->userid;
  $is_remix = !empty($_POST['original']);
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
    $clipid = $app->db->insert_id();
    $tags = $app->split_tags($_POST['tags'][$ind]);
    $app->set_clipart_tags($clipid, $tags);
    
    if($is_remix) $app->set_remix($clipid, $_POST['original']);
  }
});

$app->get("/upload/remix/:id", function($id) use($app){
  if(empty($id)) return $app->notFound();
  $id = intval($id);
  $clipart = $app->get_clipart($id);
  if(empty($clipart)) return $app->notFound();
  $clipart['filename_png'] = $app->clipart_filename_png($clipart['filename']);
  return $app->render("upload", array(
    'is_remix' => true
    , 'clipart' => $clipart
    , 'uri' => $app->request()->getResourceUri()
  ));
});

?>