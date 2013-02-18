<?php

$app->get("/user/avatar/update/:id", function($clipartid) use($app){
  if( !$app->is_logged()) return $app->notFound();
  $user = $app->user();
  $userid = $user['id'];
  $username = $app->username_from_id($userid);
  $app->user_set_avatar($username, $clipartid);
  @unlink($app->config->root_directory . "/avatar/$userid.png");
  $app->redirect("/profile/$username", null, 307);
});

$app->get("/avatars/:id", function($id) use($app){
  if(!preg_match('/(.*).png(?:.*)$/',$id, $m)) return $app->notFound();
  $id = $m[1];
  $user = $app->get_user($id);
  if(!$user) return $app->notFound();
  $userid = $user['id'];
  $clipart = $app->get_clipart($user['avatar']);
  if(!$clipart) $app->notFound("no clipart found");
  $svg_filename = preg_replace("/.png$/", '.svg', $clipart['filename'] );
  $dir = $app->config->root_directory;
  $username = username_from_id($clipart['id']);
  if(!isset($app->config->svg_debug) || !$app->config->svg_debug)
    $svg = $dir . "/people/$username/" . $svg_filename;
  else $svg = $dir . $app->config->example_svg;
  $app->svg->raster($svg, 75, 75, $dir . "/avatars/$userid.png");
  $app->response()->header('Content-Type', 'image/png');
  echo file_get_contents($dir . "/avatars/$userid.png");
});

?>