<?php

/**
 * @package         @pkg.name@
 * @version         @pkg.version@ @vUf@
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2016 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access to this file
defined('_JEXEC') or die;

class JFormFieldTG_LastUpdated extends JFormField
{
    /**
     *  Method to render the input field
     *
     *  @return  string  
     */
    function getInput()
    {   
        $file = JPATH_PLUGINS . "/system/tgeoip/db/GeoLite2-City.mmdb";

        if (!JFile::exists($file))
        {
            return "";
        }

        return JFactory::getDate(@filemtime($file))->format('d M Y');
    }
}