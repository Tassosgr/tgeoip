<?php

/**
 * @package         @pkg.name@
 * @version         @pkg.version@ @vUf@
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2018 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

class plgSystemTGeoIP extends JPlugin
{
    /**
     *  Joomla Application Object
     *
     *  @var  object
     */
    protected $app;

    /**
     *  Auto load plugin language 
     *
     *  @var  boolean
     */
    protected $autoloadLanguage = true;

    /**
     *  GeoIP Class
     *
     *  @var  object
     */
    private $geoIP;

    /**
     *  Load GeoIP Classes
     *
     *  @return  void
     */
    private function loadGeoIP()
    {
        $path = JPATH_PLUGINS . '/system/tgeoip';

        if (!class_exists('TGeoIP'))
        {
            if (@file_exists($path . '/helper/tgeoip.php'))
            {
                if (@include_once($path . '/vendor/autoload.php'))
                {
                    @include_once $path . '/helper/tgeoip.php';
                }
            }
        }

        $this->geoIP = new TGeoIP();
    }

    /**
     *  Listens to AJAX requests on ?option=com_ajax&format=raw&plugin=tgeoip
     *
     *  @return void
     */
    function onAjaxTGeoIP()
    {
        JSession::checkToken("request") or die('Invalid Token');

        // Only in admin
        if (!$this->app->isAdmin())
        {
            return;
        }

        $this->loadGeoIP();

        $task = $this->app->input->get('task', 'update');

        switch ($task)
        {
            // Update database and redirect
            case 'update-red': 

                $result = $this->geoIP->updateDatabase();

                if ($result === true)
                {
                    $msg = JText::_('PLG_SYSTEM_TGEOIP_DATABASE_UPDATED');
                    $msgType = 'message';
                } else
                {
                    $msgType = 'error';
                    $msg = $result;
                }

                $return = base64_decode($this->app->input->get->getBase64('return', null));
                $this->app->redirect($return, $msg, $msgType);

                break;
            // Update database
            case 'update':
                echo $this->geoIP->updateDatabase();
                break;
            // IP Lookup
            case 'get':
                $ip = $this->app->input->get('ip');
                echo json_encode($this->geoIP->setIP($ip)->getRecord());
                break;
        }
    }
}
