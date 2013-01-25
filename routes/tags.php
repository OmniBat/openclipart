<?php
$app->get("/tags/:tag", function($tag) use($app) {
  return $app->render('tags', array(
    'cliparts' => $app->clipart_by_tag($tag)
    , 'tags' => array($tag)
  ));
});

?>