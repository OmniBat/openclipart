<?php

$app->get("/detail/:id", function($id) use($app){
  return $app->redirect("/clipart/$id", null, 301);
});

?>