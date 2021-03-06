<?php
$app->map('/register', function() use ($app) {
    
    if(isset($app->config->picatcha['enabled']))
      $use_picatcha = $app->config->picatcha['enabled'];
    else $use_picatcha = false;
    
    // GET - just render the register page
    if(
        !isset($_POST['username']) 
        || !isset($_POST['password']) 
        || !isset($_POST['email'])
    ) return $app->render('register', array(
        'use_picatcha' => $use_picatcha
    ));
    
    $msg = null;
    $success = true;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    
    $response = function($msg, $success) use($app, $email, $username, $use_picatcha){
        if($success){
            $url = $app->config->root . "/login";
            $subject = 'Welcome to Open Clipart Library';
            $message = "Dear $username:\n\nYour registration at Open Clipart "
                . "Library was successful.\nPlease visit our site to sign in "
                . "and get started:\n$url";
            if(!$app->system_email($email, $subject, $message)){
                $msg = 'Your account was created but there was an error sending'
                    . 'your registration email';
                $success = false;
            }
        }
        if($success)
            return $app->redirect('/login', array('alert-success' => $msg));
        else return $app->render('register', array(
            'error' => $msg
            // so users don't need to type it twice
            , 'email' => $email
            , 'username' => $username
            , 'use_picatcha' => $use_picatcha
        ));
    };
    // Falidation
    if( strip_tags($username) !== $username 
        || preg_match('/^[0-9A-Za-z_]+$/', $username ) === 0
    ) return $response('Sorry, but the username is invalid (you can use only '
        . 'letters, numbers and underscore)', false);
    
    if($app->user_username_exists($username))
       return $response('Sorry, but that username is already taken', false);
    
    if( strlen($password) < 6 )
      return $response('Passwords must be at least 6 characters long', false);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        return $response('Sorry, but that email is invalid', false);
    
    if($app->user_email_exists($email)) 
       return $response('Sorry, but that email address is already in use',false);
    
    if($app->user_exist($username))
        return $response("Sorry, but the username \"$username\" already exists", false);
    
    if($use_picatcha && !isset($_POST['picatcha']['r']))
        return $response("Sorry, but you need to solve the picatcha to prove "
            . "that you're human", false);

    if($use_picatcha){
        require('libs/picatcha/picatchalib.php');
        $res = picatcha_check_answer($app->config->picatcha['private_key']
            , $_SERVER['REMOTE_ADDR']
            , $_SERVER['HTTP_USER_AGENT']
            , $_POST['picatcha']['token']
            , $_POST['picatcha']['r']);
        if($res->error === "incorrect-answer")
            return $response('You gave the wrong answer to Picatcha', false);
    }
    
    if(!$app->register($username, $password, $email, $full_name))
        return $response("Sorry, but something wrong happened and we couldn't "
            . "create your account", false);
    // Success!
    else return $response('Your account has been created. Now you can login.', true);
    
})->via('GET', 'POST');
?>
