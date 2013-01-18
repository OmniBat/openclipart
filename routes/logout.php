<?php
$app->get("/logout", function() use($app){
    $app->logout();
    if (isset($app->GET->redirect))
        return $app->redirect($app->GET->redirect);
    return $app->render('main', array('body' => 'You are now logged out'));
});
?>