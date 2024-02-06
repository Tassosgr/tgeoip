<?php

/**
 * @package         @pkg.name@
 * @version         @pkg.version@ @vUf@
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2023 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use NRFramework\User;

class JFormFieldTG_Lookup extends FormField
{
    /**
     *  GeoIP Class
     *
     *  @var  object
     */
    private $geoIP;

    /**
     *  Method to render the input field
     *
     *  @return  string  
     */
    public function getInput()
    {   
        // JavaScript
        $ajaxURL = Uri::base() . 'index.php?option=com_ajax&format=raw&plugin=tgeoip&task=get&' . Session::getFormToken() . '=1';

        Factory::getDocument()->addScriptDeclaration('
            document.addEventListener("DOMContentLoaded", function() {
                document.addEventListener("click", function(e) {
                    var btn = e.target.closest(".tGeoIPtest button");
                    if (!btn) {
                        return;
                    }

                    e.preventDefault();

                    ip = document.querySelector(".tGeoIPtest input").value;

                    if (!ip) {
                        alert("Please enter a valid IP address");
                        return false;
                    }

                    var data = new FormData();
                    data.append("ip", ip);

                    fetch("' . $ajaxURL . '",
                    {
                        method: "POST",
                        body: data
                    })
                    .then(function(res){ return res.json(); })
                    .then(function(response){
                        if (response) {
                            if (response.continent) {
                                document.querySelector(".tGeoIPtest .continent").innerHTML = response.continent.names.en;
                            }

                            if (response.city) {
                                document.querySelector(".tGeoIPtest .city").innerHTML = response.city.names.en;
                            }

                            if (response.country) {
                                document.querySelector(".tGeoIPtest .country").innerHTML = response.country.names.en;
                                document.querySelector(".tGeoIPtest .country_code").innerHTML = response.country.iso_code;
                            }

                            document.querySelector(".tGeoIPtest .results").style.display = "block";
                        } else {
                            alert("Invalid IP address");
                            document.querySelector(".tGeoIPtest .results").style.display = "none";
                        }
                    })

                    return false;
                })
            });
        ');

        // HTML
        $ip = User::getIP(); 

        return '<div class="tGeoIPtest">
            <input class="form-control input-medium" type="text" value="' . $ip . '"/>
            <button class="btn btn-outline-secondary">Lookup</button>
            <ul class="results" style="margin-top:20px; display:none;">
                <li>Continent: <span class="continent"></span></li>
                <li>Country: <span class="country"></span></li>
                <li>Country Code: <span class="country_code"></span></li>
                <li>City: <span class="city"></span></li>
            <ul>
        </div>';
    }
}