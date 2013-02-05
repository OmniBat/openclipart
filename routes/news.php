<?php

$app->get("/news/edit", function() use($app){
  return $app->render('news', array(
    'news' => $app->get_news()
  ));
});
?>