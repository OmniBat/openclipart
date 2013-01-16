<?php

/**
  * Error handler
  */
$app->error(function($exception) use($app) {
    //return full_exception_string($exception, "<br/>");
    return new Template('main', function() use ($exception) {
        return array(
            'login-dialog' => new Template('login-dialog', null)
            , 'content' => new Template('exception', function() use ($exception) {
                global $app;
                return array(
                    'name' => get_class($exception)
                    , 'message' => $exception->getMessage()
                    , 'file' => str_replace(
                        $app->config->root_directory
                        , ''
                        , $exception->getFile()),
                    'line' => $exception->getLine()
                    //->getTraceAsString()
                    , 'trace' => implode("\n", get_trace($exception))
                );
            })
        );
    });
});

?>