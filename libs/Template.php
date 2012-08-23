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

class TemplateException extends Exception {
    function __construct($msg) {
        Exception::__construct($msg, 101);
    }
}

interface Renderable {
    function render();
}


class Template implements Renderable {
    function __construct($name, $data_privider) {
        global $indent;
        $this->name = $name;
        $filename = "templates/${name}.template";
        if (!file_exists($filename)) {
            throw new TemplateException("file '$filename' not found");
        }
        $this->template = file_get_contents($filename);
        $this->user_data = $data_privider;
    }
    function render() {
        global $app, $indent;
        $start_time = get_time();
        $mustache = new Mustache_Engine(array(
            'escape' => function($val) { return $val; }
        ));
        $overwrite = $app->overwrite_data();
        $global = $app->globals();
        if ($this->user_data === null) {
            $data = array_merge($global, $overwrite);
            return $mustache->render($this->template, $data);
        } else {
            $user_data = $this->user_data;
            if (is_callable($this->user_data)) {
                // can't execute closure directly in php :(
                $closure = $this->user_data;
                $user_data = $closure();
                if (!$user_data) {
                    $msg = "Closure for '" . $this->name .
                        "returned no data";
                    throw new TemplateException("Closure for '" .
                                                $this->name .
                                                "returned no data");
                }
            }
            $data = array();
            foreach ($user_data as $name => $value) {
                if (isset($overwrite[$name])) {
                    $data[$name] = $get_array[$name];
                } else if (gettype($value) == 'array') {
                    $data[$name] = array();
                    $template = false;
                    foreach ($value as $k => $v) {
                        if (gettype($v) == 'object' &&
                            get_class($v) == 'Template') {
                            $data[$name][$k] = $v->render();
                            $template = true;
                        } else {
                            $data[$name][$k] = $v;
                        }
                    }
                    if ($template) {
                        $data[$name] = implode("\n", $data[$name]);
                    }
                } else if (gettype($value) == 'object' &&
                           get_class($value) == 'Template') {
                    $data[$name] = $value->render();
                } else {
                    $data[$name] = $value;
                }
            }
            $end_time = sprintf("%.4f", (get_time()-$start_time));
            $time = "<!-- Time: $end_time seconds -->";
            $data = array_merge($global,
                                array('load_time' => $time),
                                $data,
                                $overwrite);
            return $mustache->render($this->template, $data);
            /* it show begin before Doctype
            if (DEBUG) {
                return "\n<!-- begin: " . $this->name . " -->\n" .
                    $ret .
                    "<!-- end: " . $this->name . " -->\n";
            } else {
                return $ret;
            }
            */
        }
    }
}