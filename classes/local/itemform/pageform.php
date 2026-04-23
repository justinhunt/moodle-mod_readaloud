<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/03/13
 * Time: 19:31
 */

namespace mod_readaloud\local\itemform;

use \mod_readaloud\constants;

class pageform extends baseform
{

    public $type = constants::TYPE_PAGE;

    public function custom_definition() {
        //we just open media prompts because probably the user want to use these
        $this->_form->setExpanded('mediapromptsheading');
    }

}