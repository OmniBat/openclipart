<?php
$app->get("/tags/:tag", function($tag) use($app) {
  return $app->render('tags', array(
    'cliparts' => $app->clipart_by_tag($tag)
    , 'tags' => array($tag)
  ));
});

$app->get('/add-tag/:clipid/:tag', function($clipid, $tag) use($app){
  $app->set_clipart_tags($clipid, array($tag));
  return $app->halt(200);
});

?>