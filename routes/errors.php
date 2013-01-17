<?php

/**
  * Error handler
  */
$app->error(function($exception) use($app, $twig) {
    //return full_exception_string($exception, "<br/>");
    return $twig->render('exception', array(
        'name' => get_class($exception)
        , 'message' => $exception->getMessage()
        , 'file' => str_replace(
            $app->config->root_directory
            , ''
            , $exception->getFile()),
        'line' => $exception->getLine()
        //->getTraceAsString()
        , 'trace' => implode("\n", get_trace($exception))
    ));
});

?>