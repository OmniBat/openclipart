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


class DatabaseException extends Exception {
    function __construct($msg) {
        Exception::__construct($msg, 100);
    }
}

class Database {
    function __construct($host, $user, $pass, $db) {
        $this->conn = new mysqli($host, $user, $pass);
        $this->conn->select_db($db);
        if ($this->conn->connect_errno) {
            throw new DatabaseException("Connect failed: " .
                                        $this->conn->connect_error);
        }
    }
    
    function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    function query($query) {
      $time_start = microtime(true);
      error_log("query: $query");
      $ret = $this->conn->query($query);
      $time_end = microtime(true);
      $time = $time_end - $time_start;
      error_log("time: $time");
      if (!$ret) throw new DatabaseException($this->conn->error);
      return $ret;
    }
    
    function get_array($query){
        $result = array();
        $ret = $this->query($query);
        if ($ret->num_rows == 0) {
            return $result;
        }
        while ($row = $ret->fetch_assoc()) {
            $result[] = $row;
        }
        $ret->close();
        return $result;
    }
    
    function get_row($query) {
        $result = array();
        $ret = $this->query($query);
        $result = $ret->fetch_assoc();
        $ret->close();
        return $result;
    }
    
    function get_assoc($query) {
        $ret = $this->query($query);
        if ($ret->num_rows == 0) {
            return array();
        }
        $result = $ret->fetch_assoc();
        $ret->close();
        return $result;
    }
    
    function get_obj($query){
      $ret = $this->query($query);
      if($ret->num_rows === 0) return new stdClass();
      $result = $ret->fetch_object();
      $ret->close();
      return $result;
    }
    
    function insert_id(){
      return $this->conn->insert_id;
    }
    
    function get_column($query) {
        $result = array();
        $ret = $this->query($query);
        if ($ret->num_rows == 0) {
            return $result;
        }
        while ($row = $ret->fetch_row()) {
            $result[] = $row[0];
        }
        $ret->close();
        return $result;
    }
    
    function get_value($query, $detaul=null) {
        $result = array();
        $ret = $this->query($query);
        $result = $ret->fetch_row();
        if ($result) {
            $ret->close();
            return $result[0];
        } else {
            return $result;
        }
    }
    
    function __call($name, $argv) {
        return call_user_func_array(array($this->conn, $name), $argv);
    }
}