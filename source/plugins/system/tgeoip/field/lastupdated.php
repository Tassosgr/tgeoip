<?php

/**
 * @package         @pkg.name@
 * @version         @pkg.version@ @vUf@
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2024 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access to this file

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;

class JFormFieldTG_LastUpdated extends FormField
{
    /**
     *  Method to render the input field
     *
     *  @return  string  
     */
    public function getInput()
    {   
        $file = JPATH_PLUGINS . '/system/tgeoip/db/GeoLite2-City.mmdb';

        if (!File::exists($file))
        {
            return '';
        }

        return Factory::getDate(@filemtime($file))->format('d M Y H:m');
    }
}