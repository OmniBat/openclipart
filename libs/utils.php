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


// taken from old OCAL (author unknown)
function human_date($date) {
    $timestamp = strtotime($date);
    if ($timestamp >= strtotime("-1 minutes"))
        return "1 minute ago";
    if ($timestamp >= strtotime("-2 minutes"))
        return "2 minutes ago";
    if ($timestamp >= strtotime("-3 minutes"))
        return "3 minutes ago";
    if ($timestamp >= strtotime("-4 minutes"))
        return "4 minutes ago";
    if ($timestamp >= strtotime("-5 minutes"))
        return "5 minutes ago";
    if ($timestamp >= strtotime("-10 minutes"))
        return "10 minutes ago";
    if ($timestamp >= strtotime("-30 minutes"))
        return "half an hour ago";
    if ($timestamp >= strtotime("-1 hours"))
        return "1 hour ago";
    if ($timestamp >= strtotime("-2 hours"))
        return "2 hours ago";
    if ($timestamp >= strtotime("-3 hours"))
        return "3 hours ago";
    if ($timestamp >= strtotime("-4 hours"))
        return "4 hours ago";
    if ($timestamp >= strtotime("-5 hours"))
        return "5 hours ago";
    if ($timestamp >= strtotime("-6 hours"))
        return "6 hours ago";
    if ($timestamp >= strtotime("-7 hours"))
        return "7 hours ago";
    if ($timestamp >= strtotime("-8 hours"))
        return "8 hours ago";
    if ($timestamp >= strtotime("-9 hours"))
        return "9 hours ago";
    if ($timestamp >= strtotime("-24 hours"))
        return "today";
    if ($timestamp >= strtotime("-1 days"))
        return "yesterday";
    if ($timestamp >= strtotime("-7 days"))
        return "on ".date("l",$timestamp);
    if ($timestamp >= strtotime("-1 week"))
        return "1 week ago";
    if ($timestamp >= strtotime("-2 week"))
        return "2 weeks ago";
    else
        return date("d.m.Y",$timestamp);
}

function get_time() {
    return (float)array_sum(explode(' ', microtime()));
}

// calulate number from $min to 100 for $max, used for tag cloud
function size($min, $max) {
    return function($count) use($min, $max) {
        return round((((100-$min) * $count) / $max) + $min);
    };
}

function normalized_get_array() {
    return array_map(function($val) {
        if ($val == 'true' ||
            $val == 'false' ||
            is_numeric($val) ||
            preg_match("/^ *(\[|\{)/", $val)) { // json array or json object
            return json_decode($val);
        } else {
            return $val;
        }
    }, $_GET);
}

// array_filter check only values
function filter_pair($array, $fun) {
    $result = array();
    if (empty($array)) {
        return $result;
    }
    foreach($array as $k => $v) {
        if ($fun($k, $v)) {
            $result[$k] = $v;
        }
    }
    return $result;
}

function query_sring($array) {
    $result = array();
    foreach ($array as $k => $v) {
        $result[] = "$k=$v";
    }
    return implode('&', $result);
}

function get($array, $name, $default) {
    if (isset($array[$name])) {
        return $array[$name];
    } else {
        return $default;
    }
}

function get_object_methods($object) {
    return gettype($object) == 'object' ? get_class_methods(get_class($object)) : array();
}

function have_method($object, $method) {
    return in_array($method, get_object_methods($object));
}


function get_trace($exception) {
    $i = 0;
    return array_map(function($array) use (&$i) {
        $args = implode(', ', array_map(function($arg) {
            $type = gettype($arg);
            return $type == 'object' ? get_class($arg) : $type;
        }, $array['args']));
        $result = sprintf('%3d: ', $i++);
        if (isset($array['class']) && isset($array['type'])) {
            $result .= $array['class'] . $array['type'];
        }
        $result .= $array['function'] . '(' . $args . ')';
        if (isset($array['file'])) {
            $result .= ' in ' . str_replace($_SERVER['DOCUMENT_ROOT'],
                                            '',
                                            $array['file']);
        }
        if (isset($array['line'])) {
            $result .= ' at ' . $array['line'];
        }
        return $result;
    }, $exception->getTrace());
}

function exception_string($exception) {
    return get_class($exception) . " " . $exception->getMessage() . " in file " .
        str_replace($_SERVER['DOCUMENT_ROOT'], '', $exception->getFile()) .
        ' at ' . $exception->getLine();

}

function full_exception_string($exception, $separator="\n") {
    return exception_string($exception) . $separator .
        implode($separator, get_trace($exception));
}