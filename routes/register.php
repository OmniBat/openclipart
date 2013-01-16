<?php
$app->map('/register', function() use ($app) {
    // TODO: try catch that show json on ajax and throw exception so it will be cached
    //       by main error handler
    
    $use_picatcha = $app->config->picatcha['enabled'];
    
    // GET - just render the register page
    if(
        !isset($_POST['username']) 
        || !isset($_POST['password']) 
        || !isset($_POST['email'])
    ) return new Template('main', array(
        'content' => new Template('register', array(
            'use_picatcha' => $use_picatcha
        ))
    ));
    
    $msg = null;
    $success = true;
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    
    
    $response = function($msg, $success) use($app, $email, $username){
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
        // respond AJAX request
        if($app->request()->isAjax())
            return json_encode(array('message' => $msg, 'status' => $success));
        
        // success response
        if($success)
            return $app->redirect('/login', array('alert-success' => $msg));
        // failure response
        else return new Template('main', array(
            'content' => new Template('register', array(
                'error' => $msg
                // so users don't need to type it twice
                , 'email' => $email
                , 'username' => $username
                , 'use_picatcha' => $use_picatcha
            ))
        ));
    };
    
    
    if ( strip_tags($username) !== $username 
        || preg_match('/^[0-9A-Za-z_]+$/', $username ) === 0
    ) return $response('Sorry, but the username is invalid (you can use only '
        . 'letters, numbers and underscore)', false);
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        return $response('Sorry, but that email is invalid', false);
    
    if($app->user_exist($username))
        // TODO: check if email exists - don't allow for two accounts with the 
        // same email
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
    
    if(!$app->register($username, $password, $email))
        return $response("Sorry, but something wrong happened and we couldn't "
            . "create your account", false);
    // Success!
    else return $response('Your account has been created. Now you can login.', true);
    
})->via('GET', 'POST');
?>