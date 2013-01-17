<?php
$app->get("/profile", function() use($app, $twig) {
    return $twig->render('profile.template');
});
?>