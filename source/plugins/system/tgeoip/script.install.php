<?php
/**
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2015 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
*/

defined('_JEXEC') or die;

require_once __DIR__ . '/script.install.helper.php';

class PlgSystemTGeoIPInstallerScript extends PlgSystemTGeoIPInstallerScriptHelper
{
	public $name = 'TGEOIP';
	public $alias = 'tgeoip';
	public $extension_type = 'plugin';
}
