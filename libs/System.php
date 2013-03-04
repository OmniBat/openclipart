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
require_once('obj.php');
use \Slim\Slim as Slim;
Slim::registerAutoloader();
require_once('Database.php');
require_once('utils.php');
require_once('json-rpc/json-rpc.php');
require_once ('validators.php');


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
    // act as other user
    function disguise($id) {
        $this->system->__authorize("id = " . intval($id));
    }
}
class System extends Slim {
    public $groups;
    private $original_config;
    private $logged_user;
    public $config;
    public $db;
    public $GET;
    // TODO: rename `$user` just `$user`
    private $user;
    public $validate;
    public $validators;
    function __construct($settings) {
        global $validate;
        global $validators;
        Slim::__construct(array('debug' => false));
        session_start();
        if (gettype($settings) !== 'array') {
            throw new Exception("System Argument need to be an array " .
                                "or a function that return an array");
        }
        $this->settings = array_merge(array(
            'debug' => false,
        ), $settings);
        $this->db = new Database($settings['db_host']
                      , $settings['db_user']
                      , $settings['db_pass']
                      , $settings['db_name']);
        $this->groups = array();
        $this->user = array();
        $this->validate = $validate;
        $this->validators = $validators;
        // restore from session
        if (isset($_SESSION['userid'])) {
            try {
                // sanity check
                $id = intval($_SESSION['userid']);
                $this->__authorize("id = $id");
                $settings['userid'] = $id;
            } catch (AuthorizationException $e) {
                session_destroy();
                throw new SystemException("Invalid UserID in Session");
            }
        }
        // debug
        // $settings['system_warnings'][] = 'hello';
        if (isset($_GET['token'])) {
            $token = $this->db->escape($_GET['token']);
            try {
                $this->__authorize("token = '$token' and token_expiration > now()");
                $_SESSION['userid'] = $this->user['id'];
                // TODO clear token
            } catch (AuthorizationException $e) {
                $settings['system_warnings'][] = 'Invalid Token';
            }
        }
        //query paramters that will be forward to urls
        // TODO: this is Template specific code should be in different place
        if(isset($_SERVER['SCRIPT_URL'])) 
            $clean_uri = $_SERVER['SCRIPT_URL'];
        else $clean_uri = '';
        if (($forward = $this->get_forward_args()) != array()) {
            $settings['forward_query'] = '?' . query_sring($forward);
            $settings['redirect'] = '&';
            $clean_uri .= $settings['forward_query'];
        } else {
            $settings['redirect'] = '?';
        }
        // for login url
        $settings['redirect'] .= 'redirect=' . $settings['root'] . urlencode($clean_uri);
        if ($this->can_overwrite_config()) {
            $settings = array_merge($settings, normalized_get_array());
        }
        $this->config_array = $settings;
        $this->config = (object) $settings;
        $this->GET = (object) normalized_get_array();
        $root_dir = $this->config->root_directory;
        $app = $this;
        $this->post('/rpc/:name', function($name) use ($root_dir, $app) {
            $filename = $root_dir . "/rpc/".$name.".php";
            if (file_exists($filename)) {
                if (class_exists($name)) {
                    return handle_json_rpc(new $name());
                } else {
                    require_once($filename);
                    return handle_json_rpc(new $name());
                }
            } else {
                return json_error_string(108, "ERROR: service `$name' not found");
            }
        });
        $this->get('/rpc/:name', function($name) use ($app) {
            $app->response()->header('Content-Type', 'application/json');
            return json_error_string(108, "ERROR: You need to use POST method");
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
      
      // helper middleware
      $self = $this;
      if(!isset($this->ware)) $this->ware = new Obj;
      $this->ware->is = function($group) use($self){
        return function() use($self, $group){
          if(!$self->is($group)) return $self->notFound();
        };
      };
    }
    function validate($fields){
      $cb = $this->validate;
      return $cb($fields);
    }
    function random_token() {
        return sha1(array_sum(explode(' ', microtime())));
    }
    function user_exist($username) {
        $username = $this->db->escape($username);
        $query = "SELECT count(*) FROM openclipart_users WHERE username = '$username'";
        return $this->db->get_value($query);
    }
    function hash_pw($password){
      return md5(md5($password));
    }
    // ---------------------------------------------------------------------------------
    function login($username, $password) {
        $username = $this->db->escape($username);
        $password = $this->db->escape($password);
        $where = "username = '$username'";
        try {
            $this->__authorize($where);
        } catch (AuthorizationException $e) {
            $this->user = array();
            throw new LoginException("Invalid Username");
        }
        if ($this->user['password'] != $this->hash_pw($password)) {
            $this->user = array();
            throw new LoginException("Invalid Password");
        }
        $_SESSION['userid'] = $this->user['id'];
    }

    // ---------------------------------------------------------------------------------
    function email($to, $from, $subject,  $message) {
        $headers = "Content-type: text/plain\r\n";
        $headers .= "From: $from\r\n";
        return mail($to, $subject, $message, $headers);
    }

    // ---------------------------------------------------------------------------------
    function system_email($to, $subject, $message) {
        $from = 'noreply@' . $_SERVER['HTTP_HOST'];
        return $this->email($to, $from, $subject, $message);
    }
    // ---------------------------------------------------------------------------------
    // hours - token expiration
    function send_reset_password_link($email, $hours=1) {
        $hours = intval($hours);
        $token = $this->random_token();
        $email = $this->db->escape($email);
        $query = "SELECT username FROM openclipart_users WHERE email = '$email'";
        if ($user = $this->db->get_value($query)) {
            $query = "UPDATE openclipart_users SET token = '$token', token_expiration = ADDDATE(NOW(), interval $hours hour) WHERE username = '$user'";
            if (!$this->db->query($query)) {
                return false;
            }
            $url = $this->config->root . '/profile?token=' . $token;
            $message = "Hi $user,\n\nDid you forget your password?\n\nHere is "
                . "a link to your profile where you can change it, you will "
                . "have access to the whole site using a token in this url, it "
                . "will expire after an hour. $url\n\nRegards\nOpen Clipart "
                . "Team";
            $subject = "Open Clipart Access Link";
            return $this->system_email($email, $subject, $message);
        } else {
            return false;
        }
    }
    function user_username_exists($username){
      $username = $this->db->escape($username);
      $query = "SELECT id FROM openclipart_users WHERE username = '$username' LIMIT 1";
      return 0 !== count($this->db->get_array($query));
    }
    function user_email_exists($email){
      $email = $this->db->escape($email);
      $query = "SELECT id FROM openclipart_users WHERE email = '$email' LIMIT 1";
      return 0 !== sizeof($this->db->get_array($query));
    }
    // ---------------------------------------------------------------------------------
    function register($username, $password, $email, $full_name) {
        $username = $this->db->escape($username);
        $email = $this->db->escape($email);
        $full_name = $this->db->escape($full_name);
        $password = $this->db->escape($password);
        $password = $this->hash_pw($password);
        return $this->db->query("INSERT INTO 
            openclipart_users(
                username
                , password
                , email
                , creation_date
                , full_name
            ) VALUES(
                '$username'
                , '$password'
                , '$email'
                , now()
                , '$full_name'
            )");
    }
    
    // ---------------------------------------------------------------------------------
    function is($group) {
        return in_array($group, $this->groups);
    }
    // ---------------------------------------------------------------------------------
    function user_by_name($username) {
        return User::by_name($username);
    }
    // ---------------------------------------------------------------------------------
    // SystemFunctions class need this function for disguise php have not friend
    // param $where is part of sql that will authorize
    function __authorize($where = null) {
        if ($where == null || $where == '') {
            throw new Exception("where argument to __authorize private "
              . "method can't be null or empty");
        }
        $query = "SELECT * FROM openclipart_users WHERE $where";
        $db_user = $this->db->get_assoc($query);
        if(empty($db_user))
            throw new AuthorizationException("Where '$where' is invalid");
        $this->user = $db_user;
        $this->groups = $this->fetch_groups(intval($this->user['id']));
    }
    
    // ---------------------------------------------------------------------------------
    private function fetch_groups($id) {
        $query = "SELECT name 
          FROM openclipart_user_groups 
          INNER JOIN openclipart_groups ON id = user_group 
          WHERE user = $id";
        return $this->db->get_column($query);
    }
    
    // ---------------------------------------------------------------------------------
    function get_forward_args() {
        if (isset($this->settings['forward_query_list'])) {
            $forward = $this->settings['forward_query_list'];
            return filter_pair($_GET, function($k, $v) use ($forward) {
                return array_key_exists($k, $forward) && preg_match($forward[$k], $v);
            });
        } else {
            return array();
        }
    }
    
    // ---------------------------------------------------------------------------------
    function logout() {
        unset($_SESSION['userid']);
        session_destroy();
        $this->user = array();
    }
    
    function user(){
        return $this->user;
    }
    
    // ---------------------------------------------------------------------------------
    function can_overwrite_config() {
        return $this->is_admin();
    }
    
    // ---------------------------------------------------------------------------------
    // this user can set data passed to mustache via query string
    function can_overwrite() {
        return $this->is_admin();
    }
    
    // ---------------------------------------------------------------------------------
    function overwrite_data() {
        if ($this->can_overwrite()) {
            return normalized_get_array();
        } else {
            return array();
        }
    }
    
    // ---------------------------------------------------------------------------------
    function globals() {
        return array_merge($this->config_array, $this->user);
    }
    
    // ---------------------------------------------------------------------------------
    function track() {
      if(isset($this->GET->track)) return $this->GET->track;
      else return true;
    }
    
    // ---------------------------------------------------------------------------------
    function is_logged() {
      return isset($this->user['id']) && intval($this->user['id']) > -1;
    }
    
    // ---------------------------------------------------------------------------------
    function is_admin() {
        return $this->is_logged() && $this->is('admin');
    }
    
    // ---------------------------------------------------------------------------------
    function exception($handler) {
        $this->error = $handler;
    }
    
    function redirect($url, $qs = array(), $status = 302){
      if(!empty($qs)) $url .= '?' . http_build_query($qs);
      parent::redirect($url, $status);
    }
    function end($body, $status = 200){
      $this->status($status);
      $res = $this->response();
      $res->body($body);
    }
}
