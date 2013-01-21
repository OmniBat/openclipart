<?php

/**
  * Error handler
  */
$app->error(function($exception) use($app) {
    //return full_exception_string($exception, "<br/>");
    return $app->render('exception', array(
        'name' => get_class($exception)
        , 'message' => $exception->getMessage()
        , 'file' => str_replace(
            $app->config->root_directory
            , ''
            , $exception->getFile())
            , 'line' => $exception->getLine()
            , 'trace' => implode("\n", get_trace($exception)
          )
    ));
});

?>