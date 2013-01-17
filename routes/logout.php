<?php
$app->get("/logout", function() use($app, $twig){
    $app->logout();
    if (isset($app->GET->redirect))
        return $app->redirect($app->GET->redirect);
    return $twig->render('main.template', array('body' => 'You are now logged out'));
});
?>