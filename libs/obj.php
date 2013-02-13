<?php
class Obj{
  public function __call($method, $args) {
    if (isset($this->$method) === true) {
      $func = $this->$method;
      return call_user_func_array($func, $args);
    }else throw new Exception("Undefined method $method");
  }
}
?>