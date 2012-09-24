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

require_once('mustache.php/src/Mustache/Autoloader.php');
Mustache_Autoloader::register();

require_once('utils.php');

class TemplateException extends Exception { }

interface Renderable {
    function render();
}

class Template implements Renderable {
    function __construct($name, $data_privider=null) {
        global $indent;
        $this->name = $name;
        $this->filename = "templates/${name}.template";
        $this->user_data = $data_privider;
    }
    function apply($data, $partials=array()) {
        $mustache = new Mustache_Engine(array(
            'escape' => function($val) {
                return $val;
            },
            'partials' => $partials
        ));
        return $mustache->render($this->template, $data);
    }
    function expand() {
        global $app;
        if ($app->config->debug) {
            return "\n<!-- begin: " . $this->name . " -->\n"
                . $this->render_as_partial() .
                "<!-- end: " . $this->name . " -->\n";
        } else {
            return $this->render_as_partial();
        }
    }
    function render_as_partial() {
        return '{{=[NOPARSE[ ]NOPARSE]=}}' . $this->render();
    }
    function render() {
        global $app, $indent;
        try {
            if (!file_exists($this->filename)) {
                throw new TemplateException("file '{$this->filename}' not found");
            }
            $this->template = file_get_contents($this->filename);
            $start_time = get_time();
            $overwrite = $app->overwrite_data();
            $global = $app->globals();
            if ($this->user_data === null) {
                $data = array_merge($global, $overwrite);
                return $this->apply($data);
            } else {
                $user_data = $this->user_data;
                if (is_callable($this->user_data)) {
                    // can't execute closure directly in php :(
                    $closure = $this->user_data;
                    $user_data = $closure();
                }
                $data = array();
                $partials = array();
                if (is_array($user_data)) {
                    foreach ($user_data as $name => $value) {
                        if (isset($overwrite[$name])) {
                            $data[$name] = $overwrite[$name];
                        } else if (gettype($value) == 'array') {
                            $data[$name] = array();
                            foreach ($value as $k => $v) {
                                if ($v instanceof Template) {
                                    $partials[$name][$k] = $v->expand();
                                } else {
                                    $data[$name][$k] = $v;
                                }
                            }
                            if (isset($partials[$name])) {
                                $partials[$name] = //'{{=[NOPARSE[ ]NOPARSE]=}}' .
                                    implode("\n", $partials[$name]);
                            }
                        } else if ($value instanceof Template) {
                            $partials[$name] = $value->expand();
                        } else {
                            $data[$name] = $value;
                        }
                    }
                }
                $end_time = sprintf("%.4f", (get_time()-$start_time));
                $time = "<!-- Time: $end_time seconds -->";
                $data = array_merge($global,
                                    array('load_time' => $time),
                                    $data,
                                    $overwrite);
                return $this->apply($data, $partials);
                /* it show begin before Doctype -- there should be pragma that disable this
                   if (DEBUG) {
               return "\n<!-- begin: " . $this->name . " -->\n" .
               $ret .
               "<!-- end: " . $this->name . " -->\n";
               } else {
               return $ret;
               }
                */
            }
        } catch (Slim_Exception_Stop $e) {
        } catch (Exception $e) {
            $app->error($e);
        }
    }
    function __toString() {
        try {
            return $this->render();
        } catch (Slim_Exception_Stop $e) {
        } catch (Exception $e) {
            echo full_exception_string($e, "<br/>");
            exit();
        }
    }
}