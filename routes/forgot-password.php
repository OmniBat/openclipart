<?php
$app->map('/forgot-password', function() use ($app, $twig) {
    if(!isset($_GET['email'])) 
        return $twig->render('forgot-password.template');
    $email = $_GET['email'];
    if ($app->send_reset_password_link($email, $app->config->token_expiration)) {
        $msg = "Instant access link was send to your email";
        $error = false;
    } else {
        $msg = "We couldn't send an email, maybe you put wrong email adress";
        $error = true;
    }
    if ($app->request()->isAjax()){
        return json_encode(array('result' => $msg, 'error' => $error));
    }else{
        return $twig->render('main', array('content' => $msg));
    }
})->via('GET', 'POST');

?>