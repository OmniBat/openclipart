<?php

$app->get('/forgot-password', function() use($app){
  return $app->render('forgot-password');
});

$app->post('/forgot-password', function() use($app){
  if(empty($_POST['email'])) return $app->notFound();
  $email = $_POST['email'];
  if(!filter_var($email, FILTER_VALIDATE_EMAIL))
    return $app->render('forgot-password', array(
      'errors' => array(
        'email' => 'invalid email address'
      )
    ));
  if ($app->send_reset_password_link($email, $app->config->token_expiration)){
    $msg = "Instant access link was send to your email!";
    $error = false;
  }else{
    $msg = "No user exists with that email address.";
    $error = true;
  }
  return $app->render('forgot-password', array(
    'msg' => $msg
    , 'errors' => $error
  ));
});

?>
