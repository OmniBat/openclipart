<?php
$app->get("/logout", function() use($app){
    $app->logout();
    if (isset($app->GET->redirect))
        return $app->redirect($app->GET->redirect);
    $app->redirect("/login");
});
?>
