<?php

require_once('Slim/Slim/View.php');

class View extends \Slim\View{
    public function render($template){
        global $twig;
        if(substr($template,-9) !== '.template') $template .= '.template';
        return $twig->render($template, $this->data);
    }
}
?>