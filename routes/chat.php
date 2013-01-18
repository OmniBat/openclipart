<?php
$app->get("/chat", function() use($app) {
    return $app->render('chat');
});
?>