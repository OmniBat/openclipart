<?php

require_once('utils.php');

class PermissionException extends Exception { }

class RestrictObject {
    function __construct($object, $permissions, $groups=array()) {
        $this->object = $object;
        $this->permissions = $permissions;
        $this->groups = $groups;
    }
    function __call($name, $args) {
        foreach ($this->groups as $group) {
            if (is_array($group, $this->permissions)) {
                return call_user_func_array(array($this->object, $name), $args);
            }
        }
        throw new PermissionException("You don't have permissions to call " .
                                      "methods in this object");
    }
}

//$rcp = new RestrictObject(new Admin(), array('admin'), $app->groups);


class Restrict {
    function __construct($object, $spec, $groups=array()) {
        $this->object = $object;
        $this->groups = $groups;
        $this->permissions = get($spec, 'permissions', array());
        $this->silent = get($spec, 'silent', array());
        $this->disabled = get($spec, 'disabled', array());
    }
    function __call($name, $args) {
        $method_spec = array($this->object, $name);
        if (in_array($name, $this->disabled)) {
            if (!in_array($name, $this->silent)) {
                throw new PermissionException("Method '$name' is disabled");
            }
        } else if (in_array($name, array_keys($this->permissions))) {
            foreach ($this->groups as $group) {
                if (in_array($group, $this->permissions[$name])) {
                    return call_user_func_array($method_spec, $args);
                }
            }
            if (!in_array($name, $this->silent)) {
                // function that only don't execute
                throw new PermissionException("You can't call '$name' method on " .
                                              "this object");
            }
        } else {
            return call_user_func_array($method_spec, $args);
        }
    }
}




