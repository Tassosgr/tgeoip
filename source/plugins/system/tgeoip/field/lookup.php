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

// No direct access to this file
defined('_JEXEC') or die;

class JFormFieldTG_Lookup extends JFormField
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
    function getInput()
    {   
        // JavaScript
        $ajaxURL = JURI::base() . 'index.php?option=com_ajax&format=raw&plugin=tgeoip&task=get&' . JSession::getFormToken() . '=1';

        JFactory::getDocument()->addScriptDeclaration('
            jQuery(function($) {
                $(".tGeoIPtest button").click(function() {

                    ip = $(".tGeoIPtest input").val();

                    if (!ip) {
                        alert("Please enter a valid IP address");
                        return false;
                    }

                    $.ajax({ 
                        type: "POST",
                        url: "' . $ajaxURL . '",
                        dataType: "json",
                        data: { 
                            ip: "" + ip + ""
                        },
                        success: function(response) {
                            if (response) {
                                
                                if (response.continent) {
                                    $(".tGeoIPtest .continent").html(response.continent.names.en);
                                }

                                if (response.city) {
                                    $(".tGeoIPtest .city").html(response.city.names.en);
                                }

                                if (response.country) {
                                    $(".tGeoIPtest .country").html(response.country.names.en);
                                    $(".tGeoIPtest .country_code").html(response.country.iso_code); 
                                }

                                $(".tGeoIPtest .results").fadeIn("fast");
                            } else {
                                alert("Invalid IP address");
                                $(".tGeoIPtest .results").fadeOut("fast");
                            }
                        }
                    });

                    return false;
                })
            });
        ');

        // HTML
        $ip = $_SERVER['SERVER_ADDR']; 

        $html[] = '<div class="tGeoIPtest">';
        $html[] = '<input type="text" value="' . $ip . '"/>';
        $html[] = '<button class="btn">Lookup</button>';
        $html[] = '<ul class="results" style="margin-top:20px; display:none;">';
        $html[] = '<li>Continent: <span class="continent"></span></li>';
        $html[] = '<li>Country: <span class="country"></span></li>';
        $html[] = '<li>Country Code: <span class="country_code"></span></li>';
        $html[] = '<li>City: <span class="city"></span></li>';
        $html[] = '<ul>';
        $html[] = '</div>';

        return implode(" ", $html);
    }
}