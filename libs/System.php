<?php
/**
 *  This file is part of Open Clipart Library <http://openclipart.org>
 *
 *  Open Clipart Library is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Open Clipart Library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Open Clipart Library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *  author: Jakub Jankiewicz <http://jcubic.pl>
 */


require_once('Slim/Slim/Slim.php');
require_once('Database.php');
require_once('ArrayObjectFacade.php');
require_once('nwexceptions.php');
require_once('Restrict.php');


class SystemException extends Exception { }
// internal Exception
class AuthorizationException extends Exception { }
// user handler exception
class LoginException extends Exception { }


Class SystemFunctions {
    function __construct($system) {
        $this->system = $system;
    }
    function create_group($group) {
        //
    }
    function add_to_group($user, $group) {
        //
    }
    function disguise($id) {
        $this->system->__authorize("id = " . intval($id));
    }
}


final class System extends Slim {
    public $groups;
    private $original_config;
    public $config;
    public $db;
    public $GET;
    private $db_prefix;
    private $rest_user_data;
    function __construct($args) {
        Slim::__construct();
        session_start();
        if (is_callable($args)) {
            $args = $args();
        }
        if (gettype($args) !== 'array') {
            throw new Exception("System Argument need to be an array " .
                                "or a function that return an array");
        }
        $this->db = new Database($args['db_host'],
                                 $args['db_user'],
                                 $args['db_pass'],
                                 $args['db_name']);
        $this->groups = array();
        $this->db_prefix = $args['db_prefix'];
        $this->rest_user_data = array();
        // restor from session
        if (isset($_SESSION['userid'])) {
            try {
                // sanity check
                $id = intval($_SESSION['userid']);
                $this->__authorize("id = $id");
                $args['userid'] = $id;
            } catch (AuthorizationException $e) {
                session_destroy();
                throw new SystemException("Invalid UserID in Session");
            }
        }
        //query paramters that will be forward to urls
        // TODO: this is Template specific code should be in different place
        $clean_uri = $_SERVER['SCRIPT_URL'];
        if (($forward = $this->get_forward_args()) != array()) {
            $args['forward_query'] = '?' . query_sring($forward);
            $args['redirect'] = '&';
            $clean_uri .= $args['forward_query'];
        } else {
            $args['redirect'] = '?';
        }
        // for login url
        $args['redirect'] .= 'redirect=' . $args['root'] . urlencode($clean_uri);
        if ($this->can_overwrite_config()) {
            $args = array_merge($args, normalized_get_array());
        }
        $this->config_array = $args;
        $this->config = new ArrayObjectFacade($args);
        $this->GET = new ArrayObjectFacade(normalized_get_array());
        if (isset($this->config->permissions)) {
            // restrict access to functions
            $silent = array('disguise');
            if (isset($this->config->permissions['silent'])) {
                $silent = array_merge($silent,
                                      $this->config->permissions['silent']);
            }
            $this->functions = new Restrict(new SystemFunctions($this),
                                            $this->config->permissions['access'],
                                            $this->groups,
                                            $silent);
        }
        // act as user
        if (isset($this->config->user)) {
            $this->disguise($this->config->user);
        }
        // setup JSON-RPC
        $root_dir = $this->config->root_directory;
        $this->post('/rpc/:name', function($name) use ($root_dir) {
            $filename = $root_dir . "/rpc/".$name.".php";
            require('libs/json-rpc/json-rpc.php');
            if (class_exists($name)) {
                handle_json_rpc(new $name());
            } else {
                if (file_exists($filename)) {
                    require_once($filename);
                    handle_json_rpc(new $name());
                } else {
                    $msg = "ERROR: service `$name' not found";
                    echo json_encode(array(
                        "error" => array("code" => 108, "message" => $msg)
                    ));
                }
            }
        });
        /*
        $this->add(new Slim_Middleware_SessionCookie(array(
            'expires' => '60 minutes',
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => false,
            'httponly' => false,
            'name' => 'ocal_session',
            'secret' => $this->config->session_secret,
            'cipher' => MCRYPT_RIJNDAEL_256,
            'cipher_mode' => MCRYPT_MODE_CBC
        )));
        */
    }
    private function auto_render_fun($callable) {
        $app = $this;
        return function() use ($callable, $app) {
            $args = func_get_args();
                $ret = call_user_func_array($callable, $args);
                // TODO: maybe cast to string (string)$ret it will call __toString()
                //       without Template check, it will be more generic
                if ($ret instanceof Template) {
                    //render Template - Slim ignore return value
                    $app->response()->body($ret->render());
                }
        };
    }
    // patch over callable so closure can return instance of Template
    protected function mapRoute($args) {
        $last = count($args)-1;
        $args[$last] = $this->auto_render_fun($args[$last]);
        return Slim::mapRoute($args);
    }
    public function notFound($callable = null) {
        if (!is_null(($callable))) {
            $this->router->notFound($this->auto_render_fun($callable));
        } else {
            $customNotFoundHandler = $this->router->notFound();
            if (is_callable($customNotFoundHandler)) {
                call_user_func($customNotFoundHandler);
            } else {
                call_user_func(array($this, 'defaultNotFound'));
            }
            $this->response->status(404);
            $this->stop();
        }
    }
    function is($group) {
        return in_array($group, $this->groups);
    }
    // SystemFunctions class need this function for disguise php have not friend
    function __authorize($where = null) {
        if ($where == null || $where == '') {
            throw new Exception("where argument to authorize private method " .
                                "can't be null or empty");
        }
        $table = $this->db_prefix . '_users';
        $query = "SELECT * FROM $table WHERE $where";
        $db_user = $this->db->get_assoc($query);
        if (empty($db_user)) {
            throw new AuthorizationException("Where '$where' is invalid");
        }
        /*
        $this->rest_user_data = filter_pair($db_user, function($k, $v) {
            return $k != 'password';
        });
        */
        $this->rest_user_data = $db_user;
        $this->groups = $this->fetch_groups(intval($this->id));
    }
    private function fetch_groups($user) {
        $query = "SELECT name FROM openclipart_user_groups INNER JOIN openclipart_groups ON id = user_group WHERE user = " . $user;
        return $this->db->get_array($query);
    }
    function get_forward_args() {
        if (isset($this->original_config['forward_query_list'])) {
            $forward = $this->original_config['forward_query_list'];
            return filter_pair($_GET, function($k, $v) use ($forward){
                return in_array($k, $forward);
            });
        } else {
            return array();
        }
    }
    function login($username, $password) {
        $table = $this->config->db_prefix . '_users';
        $username = $this->db->escape($username);
        $password = $this->db->escape($password);
        $where = "username = '$username'";// AND password = md5(md5('$password'))";
        try {
            $this->__authorize($where);
        } catch (AuthorizationException $e) {
            $this->rest_user_data = array();
            throw new LoginException("Invalid Username");
        }
        if ($this->password != md5(md5($password))) {
            $this->rest_user_data = array();
            throw new LoginException("Invalid Password");
        }
        $_SESSION['userid'] = $this->id;
    }
    function __get($name) {
        //throw new Exception("Name $name not found");
        if (array_key_exists($name, $this->rest_user_data)) {
            return $this->rest_user_data[$name];
        } else {
            throw new Exception("'" . get_class($this) . "' have no $name " .
                                "property ");
        }
    }
    function logout() {
        unset($_SESSION['userid']);
        session_destroy();
        $this->rest_user_data = array();
    }
    function can_overwrite_config() {
        return $this->is_admin();
    }
    // this user can set data passed to mustache via query string
    function can_overwrite() {
        return $this->is_admin();
    }
    function nsfw() {
        //act as logged
        return $this->config->get('nsfw', true);
        if ($this->is_logged()) {
            return $this->config->get('nsfw', $this->nfsw);
        } else {
            return true;
        }
    }
    function overwrite_data() {
        if ($this->can_overwrite()) {
            return normalized_get_array();
        } else {
            return array();
        }
    }
    function globals() {
        return array_merge($this->config_array, $this->rest_user_data);
    }
    function track() {
        return $this->GET->get('track', true);
    }
    function is_logged() {
        return isset($this->id) && is_numeric($this->id);
    }
    function is_admin() {
        //debug
        return true;
        return $this->is_logged() && $this->is('admin');
    }
    function exception($handler) {
        $this->error = $handler;
    }
    function __call($method, $argv) {
        // $ret instanceof Template
        // return $ret->render();
        /*if (have_method($this->slim, $method)) {
            return call_user_func_array(array($this->slim, $method), $argv);
        } else*/
        if (have_method($this->functions, $method)) {
            return call_user_func_array(array($this->functions, $method), $argv);
        } else {
            throw new BadMethodCallException("Can't call method '$method'");
        }
    }
}