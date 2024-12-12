<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:31
 */

namespace mod_readaloud\local\rsquestion;

use mod_readaloud\constants;

class textpromptshortform extends baseform {


    public $type = constants::TEXTBOXCHOICE;
    public $typestring = constants::TEXTBOXCHOICE;

    public function custom_definition() {

        $this->add_correctanswer();
        $this->add_textboxresponse(1, 'answer1', true);
        $this->add_textboxresponse(2, 'answer2', true);
        $this->add_textboxresponse(3, 'answer3', true);
        $this->add_textboxresponse(4, 'answer4', true);
    }

}
