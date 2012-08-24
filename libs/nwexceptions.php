<?php
/**
 *  This file is part of Open Clipart Library <http://openclipart.org>
 *
 *  Open Clipart Library is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU Lasser General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Open Clipart Library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU Lasser General Public License
 *  along with Open Clipart Library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *  author: Andrey Nikishaev
 */
 
class NoticeException extends Exception { 
    public function __toString() {
        return  "Notice: {$this->message} {$this->file} on line {$this->line}\n";
    }
}
 
class WarningException extends Exception { 
    public function __toString() {
        return  "Warning: {$this->message} {$this->file} on line {$this->line}\n";
    }
}
 
set_error_handler("error_handler", E_ALL);
 
function error_handler($errno, $errstr) {
    if($errno == E_WARNING) {
        throw new WarningException($errstr);
    } else if($errno == E_NOTICE) {
        throw new NoticeException($errstr);
    }
}