<?php
$app->map('/login', function() use ($app) {
    $error = null;
    if($app->is_logged()) return $app->redirect("/profile");
    if (isset($_POST['login']) && isset($_POST['password'])) {
        $redirect = isset($app->GET->redirect) ? $app->GET->redirect : $app->config->root;
        // TODO: redirect don't work
        try {
            $app->login($_POST['login'], $_POST['password']);
            // login successful
            if(isset($app->GET->redirect))
              return $app->redirect($app->GET->redirect);
            else 
              return $app->redirect('/profile');
        } catch (LoginException $e) {
            $error = $e->getMessage();
        }
    }
    if(isset($_GET['alert-success'])) $alert_success = $_GET['alert-success'];
    else $alert_success = NULL;
    
    return $app->render('login', array(
        'login' => isset($_POST['login']) ? $_POST['login'] : ''
        , 'error' => $error
        , 'redirect' => isset($app->GET->redirect) ? $app->GET->redirect : ''
        , 'alert_success' => $alert_success
    ));
})->via('GET', 'POST');
?>