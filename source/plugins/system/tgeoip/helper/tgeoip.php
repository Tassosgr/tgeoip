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

defined('_JEXEC') or die;

use Tassos\Vendor\GeoIp2\Database\Reader;
use Tassos\Vendor\splitbrain\PHPArchive\Tar;
use NRFramework\User;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\Filesystem\Path;
use Joomla\CMS\Http\HttpFactory;

class TGeoIP
{
	/**
	 * The MaxMind GeoLite database reader
	 *
	 * @var    Reader
	 */
	private $reader = null;

	/**
	 * Records for IP addresses already looked up
	 *
	 * @var   array
	 *
	 */
	private $lookups = array();

	/**
	 *  Max Age Database before it needs an update
	 *
	 *  @var  integer
	 */
	private $maxAge = 30;

	/**
	 *  Database File name
	 *
	 *  @var  string
	 */
	private $DBFileName = 'GeoLite2-City';

	/**
	 *  Database Remote URL
	 *
	 *  @var  string
	 */
	private $DBUpdateURL = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=USER_LICENSE_KEY&suffix=tar.gz';

	/**
	 *  GeoIP Enable Geolocations Documentation URL
	 * 
	 *  @var  string
	 */
	private $TGeoIPEnableDocURL = 'https://www.tassos.gr/kb/general/how-to-enable-geolocation-features-in-tassos-gr-extensions';

	/**
	 *  The IP address to look up
	 *
	 *  @var  string
	 */
	private $ip;

	/**
	 *  The License Key
	 * 
	 *  @var  string
	 */
	private $key;

	/**
	 * Public constructor. Loads up the GeoLite2 database.
	 */
	public function __construct($ip = null)
	{
		if (!function_exists('bcadd') || !function_exists('bcmul') || !function_exists('bcpow'))
		{
			require_once __DIR__ . '/fakebcmath.php';
		}

		// Check we have a valid GeoLite2 database
		$filePath = $this->getDBPath();

		if (!file_exists($filePath))
		{
			$this->reader = null;
		}

		try
		{
			$this->reader = new Reader($filePath);
		}
		// If anything goes wrong, MaxMind will raise an exception, resulting in a WSOD. Let's be sure to catch everything.
		catch(\Exception $e)
		{
			$this->reader = null;
		}

		// Setup IP
        $this->ip = $ip ?: User::getIP();

		if (in_array($this->ip, array('127.0.0.1', '::1')))
		{
			$this->ip = '';
		}
	}

	/**
	 *  Sets the license key
	 * 
	 *  @param   string
	 * 
	 *  @return  mixed
	 */
	public function setKey($key)
	{
		$this->key = $key;
	}

	/**
	 *  Retrieves the key
	 * 
	 *  @return  string
	 */
	private function getKey()
	{
		if ($this->key)
		{
			return $this->key;
		}

		$plugin = PluginHelper::getPlugin('system', 'tgeoip');
		$params = new Registry($plugin->params);

		return $params->get('license_key', '');
	}

	/**
	 *  Set the IP to look up
	 *
	 *  @param  string  $ip  The IP to look up
	 */
	public function setIP($ip)
	{
		$this->ip = $ip;
		return $this;
    }

	/**
	 * Gets the ISO country code from an IP address
	 *
	 * @return  mixed  A string with the country ISO code if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryCode()
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}

		return $record->country->isoCode;
	}

	/**
	 * Gets the country name from an IP address
	 *
	 * @param   string  $locale  The locale of the country name, e.g 'de' to return the country names in German. If not specified the English (US) names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCountryName($locale = null)
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}

		if (empty($locale))
		{
			return $record->country->name;
		}

		return $record->country->names[$locale];
	}

	/**
	 * Gets the continent ISO code from an IP address
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getContinentCode($locale = null)
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}

		return $record->continent->code;
	}

	/**
	 * Gets the continent name from an IP address
	 *
	 * @param   string  $locale  The locale of the continent name, e.g 'de' to return the country names in German. If not specified the English (US) names are returned.
	 *
	 * @return  mixed  A string with the country name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getContinentName($locale = null)
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}

		if (empty($locale))
		{
			return $record->continent;
		}

		return $record->continent->names[$locale];
	}

	/**
	 * Gets a raw record from an IP address
	 *
	 * @return  mixed  A \GeoIp2\Model\City record if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getRecord()
	{
		if (empty($this->ip))
		{
			return false;
		}

		$ip = $this->ip;

		$needsToLoad = !array_key_exists($ip, $this->lookups);

		if ($needsToLoad)
		{
			try
			{
				if (!is_null($this->reader))
				{
					$this->lookups[$ip] = $this->reader->city($ip);
				}
				else
				{
					$this->lookups[$ip] = null;
				}
			}
			catch (Tassos\Vendor\GeoIp2\Exception\AddressNotFoundException $e)
			{
				$this->lookups[$ip] = false;
			}
			catch (\Exception $e)
			{
				// GeoIp2 could throw several different types of exceptions. Let's be sure that we're going to catch them all
				$this->lookups[$ip] = null;
			}
		}

		return $this->lookups[$ip];
	}

	/**
	 *  Gets the city's name from an IP address
	 *
     *  @param   string  $locale  The locale of the city's name, e.g 'de' to return the city names in German. If not specified the English (US) names are returned.
	 *  @return  mixed   A string with the city name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getCity($locale = null)
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}
        
        if (empty($locale))
        {
            return $record->city->name;    
        }

		return $record->city->names[$locale];
    }
    
    /**
	 *  Gets a geographical region's (i.e. a country's province/state) name from an IP address
	 *
     *  @param   string  $locale  The locale of the regions's name, e.g 'de' to return region names in German. If not specified the English (US) names are returned.
	 *  @return  mixed   A string with the region's name if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getRegionName($locale = null)
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}
    
        // MaxMind stores region information in a 'Subdivision' object (also found in $record->city->subdivision)
        // http://maxmind.github.io/GeoIP2-php/doc/v2.9.0/class-GeoIp2.Record.Subdivision.html
        if (empty($locale))
        {
            return $record->mostSpecificSubdivision->name;
        }

		return $record->mostSpecificSubdivision->names[$locale];
    }
    
    /**
	 *  Gets a geographical region's (i.e. a country's province/state) ISO 3611-2 (alpha-2) code from an IP address
	 *
	 *  @return  mixed   A string with the region's code if found, false if the IP address is not found, null if the db can't be loaded
	 */
	public function getRegionCode()
	{
		$record = $this->getRecord();

		if ($record === false || is_null($record))
		{
			return false;
		}

        // MaxMind stores region information in a 'Subdivision' object
        // http://maxmind.github.io/GeoIP2-php/doc/v2.9.0/class-GeoIp2.Record.Subdivision.html
        return $record->mostSpecificSubdivision->isoCode;
	}
	
	/**
	 * Downloads and installs a fresh copy of the GeoLite2 City database
	 *
	 * @return  mixed  True on success, error string on failure
	 */
	public function updateDatabase()
	{
        // Try to download the package, if I get any exception I'll simply stop here and display the error
		try
		{
			$compressed = $this->downloadDatabase();
		}
		catch (\Exception $e)
		{
			return $e->getMessage();
		}

		// Write the downloaded file to a temporary location
		$target = $this->getTempFolder() . $this->DBFileName . '.tar.gz';
		if (File::write($target, $compressed) === false)
		{
			return Text::_('PLG_SYSTEM_TGEOIP_ERR_WRITEFAILED');
		}

		// Unzip database to the same temporary location
		$tar = new Tar;
		$tar->open($target);
		$extracted_files = $tar->extract($this->getTempFolder());

		$database_file = '';
		$extracted_folder = '';

		// Loop through extracted files to find the name of the extracted folder and the name of the database file
		foreach ($extracted_files as $key => $extracted_file)
		{
			if ($extracted_file->getIsdir())
			{
				$extracted_folder = $extracted_file->getPath();
			}

			if (strpos($extracted_file->getPath(), '.mmdb') === false)
			{
				continue;
			}

			$database_file = $extracted_file->getPath();
		}

		// Move database file to the correct location
		if (!File::move($this->getTempFolder() . $database_file, $this->getDBPath()))
		{
			return Text::sprintf('PLG_SYSTEM_TGEOIP_ERR_CANTWRITE', $this->getDBPath());
		}

		// Make sure the database is readable
		if (!$this->dbIsValid())
		{
			return Text::_('PLG_SYSTEM_TGEOIP_ERR_INVALIDDB');
		}

		// Delete leftovers
		File::delete($target);
		Folder::delete($this->getTempFolder() . $extracted_folder);

		return true;
	}

	/**
	 * Double check if MaxMind can actually read and validate the downloaded database
	 *
	 * @return bool
	 */
	private function dbIsValid() 
	{
		try
		{
			$reader = new Reader($this->getDBPath());
		}
		catch (\Exception $e)
		{
			return false;
		}

		return true;
	}
	
	/**
	 * Download the compressed database for the provider
	 * 
	 * @return  string  The compressed data
	 *
	 * @throws  Exception
	 */
	private function downloadDatabase()
	{
		// Make sure we have enough memory limit
		ini_set('memory_limit', '-1');

		$license_key = $this->getKey();

		if (empty($license_key))
		{
			throw new \Exception(Text::_('PLG_SYSTEM_TGEOIP_LICENSE_KEY_EMPTY') . '&nbsp;<a href="' . $this->TGeoIPEnableDocURL . '" target="_blank">' . Text::_('PLG_SYSTEM_TGEOIP_ENABLE_DOC_LINK_LABEL') . '</a>');
		}

		$http = HttpFactory::getHttp();

		$this->DBUpdateURL = str_replace('USER_LICENSE_KEY', $license_key, $this->DBUpdateURL);

		// Let's bubble up the exception, we will take care in the caller
		$response   = $http->get($this->DBUpdateURL);
		$compressed = $response->body;

		// 401 is thrown if you have incorrect credentials or wrong license key
		if ($response->code == 401)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_TGEOIP_ERR_WRONG_LICENSE_KEY'));
		}
		
		// Generic check on valid HTTP code
		if ($response->code > 299)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_TGEOIP_ERR_MAXMIND_GENERIC'));
		}

		// An empty file indicates a problem with MaxMind's servers
		if (empty($compressed))
		{
			throw new \Exception(Text::_('PLG_SYSTEM_TGEOIP_ERR_EMPTYDOWNLOAD'));
		}

		// Sometimes you get a rate limit exceeded
		if (stristr($compressed, 'Rate limited exceeded') !== false)
		{
			throw new \Exception(Text::_('PLG_SYSTEM_TGEOIP_ERR_MAXMINDRATELIMIT'));
		}

		return $compressed;
	}

	/**
	 * Reads (and checks) the temp Joomla folder
	 *
	 * @return string
	 */
	private function getTempFolder()
	{
		$ds = DIRECTORY_SEPARATOR;

		$tmpdir = Factory::getConfig()->get('tmp_path');

		if (realpath($tmpdir) == $ds . 'tmp')
		{
			$tmpdir = JPATH_SITE . $ds . 'tmp';
		}
		
		elseif (!is_dir($tmpdir))
		{
			$tmpdir = JPATH_SITE . $ds . 'tmp';
		}

		return Path::clean(trim($tmpdir) . $ds);
	}

	/**
	 *  Returns Database local file path
	 *
	 *  @return  string
	 */
	private function getDBPath()
	{
		return JPATH_ROOT . '/plugins/system/tgeoip/db/' . $this->DBFileName . '.mmdb';
	}

	/**
	 * Does the GeoIP database need update?
	 *
	 * @return  boolean
	 */
	public function needsUpdate()
	{
		// Get the modification time of the database file
		$modTime = @filemtime($this->getDBPath());

		// This is now
		$now = time();

		// Minimum time difference
		$threshold = $this->maxAge * 24 * 3600;

		// Do we need an update?
		$needsUpdate = ($now - $modTime) > $threshold;

		return $needsUpdate;
	}
}