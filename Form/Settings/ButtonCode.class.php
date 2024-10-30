<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

class liveagent_Form_Settings_ButtonCode extends liveagent_Form_Base {

    public function createDefaultWidget() {
        $connectHelper = new liveagent_Helper_Connect();
        try {
            $connectHelper->createWidget($this->settings->getLiveAgentUrl(), $this->settings->getDefaultWidgetParams());
            return;
        } catch (liveagent_Exception_ConnectProblem $e) {
            throw new liveagent_Exception_ConnectProblem($e->getMessage());
        }
    }

    protected function getTemplateFile() {
        return $this->getTemplatesPath() . 'ButtonCode.xtpl';
    }

    protected function getOption($name) {
        if ($name === liveagent_Settings::BUTTON_CODE) {
            return $this->settings->getButtonCode();
        }
        return parent::getOption($name);
    }

    private function getAgentAuthToken() {
        if (is_admin() && current_user_can('manage_options') && current_user_can( 'install_plugins')) {
            $authToken = $this->settings->getOwnerAuthToken();
            if ($authToken === liveagent_Settings::NO_AUTH_TOKEN) {
                return '';
            }
            return $authToken;
        }
        return '';
    }

    private function getIframeHTML($contactwidgetid, $title) {
        return '<iframe frameborder="0" id="iFramePreview' . $contactwidgetid . '" width="100%" height="100%" '
               . 'style="background-color: white"><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" '
               . '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml">'
               . '<head><title>' . $title . '</title></head><body></body></html></iframe></div>';
    }

    private function prepareWidgetsBox() {
        try {
            $widgetsArray = $this->settings->getAllWidgets();
            $widgetsHTML = '';
            $saved = false;

            if (empty($widgetsArray)) {
                $connectHelper = new liveagent_Helper_Connect();
                try {
                    $widgetsArray = $connectHelper->createWidget($this->settings->getLiveAgentUrl(), $this->settings->getDefaultWidgetParams());
                } catch (liveagent_Exception_ConnectProblem $e) {
                    $this->addVariable('widgetsHTML', '<div class="widgetsContainer">{errorOccurred}' . $e->getMessage() . "</div>\n");
                }
            }

            foreach ($widgetsArray as $widget) {
                $selected = '';
                $useButton = false;

                if (($this->settings->getButtonCode() == null) || ($this->settings->getButtonCode() == '')) {
                    if (!$saved) {
                        $this->settings->setButtonCode(htmlspecialchars_decode($widget->integration_code));
                        $this->settings->setButtonId($widget->contactwidgetid);
                        $saved = true;
                        $selected = ' selected';
                    }
                    else {
                        $useButton = true;
                    }
                } else {
                    if ($this->settings->getSavedButtonId() === $widget->contactwidgetid) {
                        $selected = ' selected';
                    }
                    else {
                        $useButton = true;
                    }
                }

                $result = '<div class="widgetTitle">' . $widget->name . ':</div>';
                $result .= '<div class="widgetDisplay">' . $this->getIframeHTML($widget->contactwidgetid,$widget->name);
                $result .= '<textarea id="iFrame' . $widget->contactwidgetid . '" class="widgetCodeInvisible">' . $widget->onlinecode . '</textarea>';
                $result .= '<textarea id="' . $widget->contactwidgetid . '" class="widgetCodeInvisible">' . htmlspecialchars_decode($widget->integration_code) . '</textarea>';

                if ($useButton) {
                    $result .= '<div onclick="jQuery(function($) {$.fn.setButton(\'' . $widget->contactwidgetid . '\')})" class="widgetSetButton">{useThisButton}</div>';
                }
                else {
                    $result .= '<span class="inUseLabel">{inUse}</span>';
                }

                $widgetsHTML .= '<div class="widgetBox ' . $selected . '">' . $result . "</div>\n";
                $widgetsHTML .= '<script type="text/javascript">
					jQuery(function($) {
						$.fn.getIframePreviewCode("' . $widget->contactwidgetid . '");
					});
					</script>';
            }

            $this->addVariable('widgetsHTML', '<div class="widgetsContainer">' . $widgetsHTML . '</div>');
        } catch (liveagent_Exception_ConnectProblem $e) {
            $this->addVariable('widgetsHTML', '<div class="widgetsContainer">{errorOccurred}' . $e->getMessage() . "</div>\n");
        }
    }

    protected function initForm() {
        $this->addVariable('LiveAgentFreeHelpdeskAndLiveChat', __('Live chat and helpdesk plugin for Wordpress', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('contactHelp', __('Do you need any help with this plugin? Feel free to', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('contactLink', __('contact us', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('resetText', __('Reset everything and start over', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('integrationSectionLabel', __('Actual button code', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('otherSectionLabel', __('Additional settings', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('buttonCodeLabel', __('Button code', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('accountSectionLabel', __('Your LiveAgent account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('accountUrlLabel', __('Account URL', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('loginLabel', __('Login to your account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('ChangeLabel', __('Connect to a different account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('buttonCodeHelp', __('This is the chat button code which will be automatically placed to your Wordpress site', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('configOptionsHelp', __('If customer is logged in, you can automatically add these to chat.', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('configOptionsTitle', __('Additional options', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('customer', __('customer', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('name', __('name', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('email', __('email', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('level', __('level', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('widgetsSectionLabel', __('Your chat buttons', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('addMoreButtons', __('Add more buttons', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('LaSignupFormDesc', __('This will redirect you to your LiveAgent account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('displayInAdminPanel', __('Display button in admin panel', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('saveWidgetCodeHelp', __('The default widget code has just changed. You have to save it to apply the changes to the site chat widget.', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('saveWidgetCode', __('Save widget code', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('saveUrlAction', admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE));
        $this->addVariable('saveButtonCodeFlag', liveagent_Settings::BUTTONS_SETTINGS_PAGE_NAME);
        $this->addVariable('la-url', $this->settings->getLiveAgentUrl());
        $this->addVariable('laAgentUrl', $this->settings->getLiveAgentUrl() . 'agent/');
        $this->addVariable('agentToken', $this->getAgentAuthToken());
        $this->addVariable('ChangeUrl', admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE . '&ac=' . liveagent_Settings::ACTION_CHANGE_ACCOUNT));
        $this->addVariable('resetUrl', admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE . '&ac=' . liveagent_Settings::ACTION_RESET_ACCOUNT));
        $this->addVariable('createCustomer', __('Create customer', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('createCustomerHelp', __('The plugin will create a customer account in LiveAgent for every customer who signs up in your WordPress. It also allows you to use shortcode [customerPortalLogin] with an option to set login caption: [customerPortalLogin caption="Login to your customer portal"]', LIVEAGENT_PLUGIN_NAME));

        $this->prepareWidgetsBox();

        $code = $this->settings->getButtonCode();

        $this->addVariable('buttonId', $this->settings->getSavedButtonId());
        $this->addTextArea(liveagent_Settings::BUTTON_CODE, $code, 10 , 80, ' textarea');
        $this->addVariable('inUse', __('In use', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('errorOccurred', __('An error occurred: ', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('useThisButton', __('Use this button', LIVEAGENT_PLUGIN_NAME));

        // display in admin
        if ($this->settings->getOption(liveagent_Settings::PREVIEW_BUTTON_IN_ADMIN) != '') {
            $this->addVariable('displayInAdminChecked', ' checked');
        } else {
            $this->addVariable('displayInAdminChecked', '');
        }

        // additional configs
        if ($this->settings->getOption(liveagent_Settings::ADDITIONAL_NAME) != '') {
            $this->addVariable('configOptionNameChecked', ' checked');
        } else {
            $this->addVariable('configOptionNameChecked', '');
        }
        if ($this->settings->getOption(liveagent_Settings::ADDITIONAL_EMAIL) != '') {
            $this->addVariable('configOptionEmailChecked', ' checked');
        } else {
            $this->addVariable('configOptionEmailChecked', '');
        }
        if ($this->settings->getOption(liveagent_Settings::ADDITIONAL_LEVEL) != '') {
            $this->addVariable('configOptionLevelChecked', ' checked');
        } else {
            $this->addVariable('configOptionLevelChecked', '');
        }
        if ($this->settings->getOption(liveagent_Settings::CREATE_CUSTOMER) != '') {
            $this->addVariable('laCreateCustomerChecked', ' checked');
        } else {
            $this->addVariable('laCreateCustomerChecked', '');
        }
    }
}