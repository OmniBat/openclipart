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


class ArrayObjectFacade {
    protected $data;
    function __construct($arg) {
        $data = array();
        if ($arg !== null) {
            $this->data = $arg;
        }
    }
    function get($name, $default=null) {
        return isset($this->$name) ? $this->$name : $default;
    }
    function __get($name) {
        return $this->data[$name];
    }
    function __isset($name) {
        return in_array($name, array_keys($this->data));
    }
}