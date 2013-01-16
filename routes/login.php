<?php
$app->map('/login', function() use ($app) {
    $error = null;
    if (isset($_POST['login']) && isset($_POST['password'])) {
        $redirect = isset($app->GET->redirect) ? $app->GET->redirect : $app->config->root;
        // TODO: redirect don't work
        try {
            $app->login($_POST['login'], $_POST['password']);
            // login successful
            return $app->redirect('/profile');
        } catch (LoginException $e) {
            $error = $e->getMessage();
        }
    }
    if(isset($_GET['alert-success'])) $alert_success = $_GET['alert-success'];
    else $alert_success = NULL;
    
    return new Template('main', function() use ($error, $alert_success, $app) {
        return array(
            'login-dialog' => new Template('login-dialog', null),
            'content' => array(new Template('login', function() use ($error, $alert_success, $app) {
                return array(
                    // fill login on second attempt
                    'login' => isset($_POST['login']) ? $_POST['login'] : '',
                    'error' => $error,
                    'redirect' => isset($app->GET->redirect) ? $app->GET->redirect : ''
                    , 'alert-success' => $alert_success
                );
            }))
        );
    });
})->via('GET', 'POST');
?>