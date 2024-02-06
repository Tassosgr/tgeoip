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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

class JFormFieldTG_UpdateButton extends FormField
{
    /**
     *  Method to render the input field
     *
     *  @return  string  
     */
    public function getInput()
    {   
        if (!NRFramework\Extension::pluginIsEnabled('tgeoip'))
        {
            return '<span class="label label-warning" style="margin-top:4px;">' . Text::_('PLG_SYSTEM_TGEOIP_ENABLE_PLUGIN') . '</span>';
        }

        HTMLHelper::stylesheet('plg_system_nrframework/joomla4.css', ['relative' => true, 'version' => 'auto']);

        $ajaxURL = Uri::base() . 'index.php?option=com_ajax&format=raw&plugin=tgeoip&task=update&license_key=USER_LICENSE_KEY&' . Session::getFormToken() . '=1';

        Text::script('PLG_SYSTEM_TGEOIP_DATABASE_UPDATED');
        Text::script('PLG_SYSTEM_TGEOIP_PLEASE_WAIT');

        Factory::getDocument()->addScriptDeclaration('
            document.addEventListener("DOMContentLoaded", function() {
                document.addEventListener("click", function(e) {
                    var btn = e.target.closest(".geo button");
                    if (!btn) {
                        return;
                    }
                    
                    e.preventDefault();

                    var license_key = e.target.closest("form").querySelector("#jform_params_license_key").value;
                    if (!license_key) {
                        return;
                    }

                    var alert = document.querySelector(".geo .alert");
                
                    var url = "' . $ajaxURL . '";
                    url = url.replace("USER_LICENSE_KEY", license_key);
                    
                    // before request
                    alert.style.display = "none";
                    btn.querySelector("span").innerHTML = Joomla.Text._("PLG_SYSTEM_TGEOIP_PLEASE_WAIT");
                    btn.classList.add("btn-working");

                    fetch(url,
                    {
                        method: "POST"
                    })
                    .then(function(res){ return res.text(); })
                    .then(function(response){
                        if (response == "1") {
                            alert.innerHTML = Joomla.Text._("PLG_SYSTEM_TGEOIP_DATABASE_UPDATED");
                            alert.style.display = "block";
                            alert.classList.remove("alert-danger");
                            alert.classList.add("alert-success");
                        } else {
                            alert.innerHTML = response;
                            alert.style.display = "block";
                            alert.classList.remove("alert-success");
                            alert.classList.add("alert-danger");
                        }

                        btn.classList.remove("btn-working");
                        btn.querySelector("span").innerHTML = btn.dataset.label;
                    });

                    return false;
                });
            });
        ');

        Factory::getDocument()->addStyleDeclaration('
            .geo .btn-working {
                pointer-events:none;
            }
            .geo .alert {
                display:none;
                margin-bottom: 10px;
            }
            .geo button {
                outline:none !important;
                width: auto;
                height: auto;
                line-height: inherit;
            }
            .geo button:before {
                margin-right:5px;
                position:relative;
                top:1px;
            }
            #wrapper .geo .icon-refresh { 
                margin-right: 5px;
            }
        ');

        return '
            <div class="geo">
                <div class="alert alert-danger"></div>
                <button class="btn btn-primary" data-label="' . Text::_('PLG_SYSTEM_TGEOIP_UPDATE_DATABASE') . '">
                    <em class="icon-refresh"></em>
                    <span>' . Text::_('PLG_SYSTEM_TGEOIP_UPDATE_DATABASE') . '</span>
                </button>            
            </div>';
    }
}