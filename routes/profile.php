<?php
$app->get("/profile", function() use($app) {
    return new Template('main', array(
        'content' => new Template('profile')
    ));
});
?>