<?php

$app->get("/detail/:id", function($id) use($app){
  return $app->redirect("/clipart/$id", null, 301);
});

$app->get("/user-detail/:username", function($username) use($app){
  return $app->redirect("/profile/$username", null, 301);
});

$app->get("/manage/profile", function() use($app){
  return $app->redirect("/profile/edit", null, 301);
});

$app->get("/manage/clipart", function() use($app){
  $user = $app->user();
  if(!$user) return $app->notFound();
  $username = $user['username'];
  return $app->redirect("/profile/$username/clipart", null, 301);
});

$app->get("/collections", function() use($app){
  return $app->redirect("/", null, 301);
});

$app->get("/signin", function() use($app){
  return $app->redirect("/login", null, 301);
});

$app->get("/browse", function() use($app){
  return $app->redirect("/browse", null, 301);
});


$app->get("/bydate", function() use($app){
  return $app->redirect("/clipart/latest", null, 301);
});

$app->get("/byhits", function() use($app){
  return $app->redirect("/clipart/popular", null, 301);
});

$app->get("/multiupload", function() use($app){
  return $app->redirect("/upload", null, 301);
});

$app->get("/news", function() use($app){
  return $app->redirect("/", null, 301);
});

$app->get("/packages", function() use($app){
  return $app->redirect("/", null, 301);
});


?>