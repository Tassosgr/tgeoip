<?php

/**
 * @package         @pkg.name@
 * @version         @pkg.version@ @vUf@
 *
 * @author          Tassos Marinos <info@tassos.gr>
 * @link            http://www.tassos.gr
 * @copyright       Copyright © 2020 Tassos Marinos All Rights Reserved
 * @license         GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// No direct access to this file
defined('_JEXEC') or die;

class JFormFieldTG_UpdateButton extends JFormField
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
            return '<span class="label label-warning" style="margin-top:4px;">' . JText::_('PLG_SYSTEM_TGEOIP_ENABLE_PLUGIN') . '</span>';
        }

        $ajaxURL = JURI::base() . 'index.php?option=com_ajax&format=raw&plugin=tgeoip&task=update&license_key=USER_LICENSE_KEY&' . JSession::getFormToken() . '=1';

        JFactory::getDocument()->addScriptDeclaration('
            jQuery(function($) {
                $(".tgeoipUpdate").click(function() {

                    btn = $(this);

                    btn.removeClass("btn-danger");

                    var url = "' . $ajaxURL . '";

                    var license_key = $(this).parents("form").find(".tgeoip_license_key").val();
                    url = url.replace("USER_LICENSE_KEY", license_key);
                    
                    $.ajax({ 
                        type: "POST",
                        url: url,
                        success: function(response) {
                            if (response == "1") {
                                btn.html("Database updated!").addClass("btn-success");
                            } else {
                                btn.html(response).addClass("btn-danger").removeClass("btn-working"); 
                            }
                        },
                        beforeSend: function() {
                            btn.html("Downloading Updates. Please wait..").addClass("btn-working");
                        }
                    });

                    return false;
                })
            }) 
        ');

        JFactory::getDocument()->addStyleDeclaration('
            .btn-working {
                pointer-events:none;
            }
        ');

        return '<a class="btn btn-primary tgeoipUpdate" href="#"><span class="icon-refresh"></span> Update Database</a>';
    }
}