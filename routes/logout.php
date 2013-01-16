<?php
$app->get("/logout", function() {
    global $app;
    $app->logout();
    if (isset($app->GET->redirect)) {
        $app->redirect($app->GET->redirect);
    } else {
        return new Template('main', function() {
            return array(
                'content' => '<p>You are now logged out</p>'
            );
        });
    }
});
?>