<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

class liveagent_Form_Settings_Account extends liveagent_Form_Base {

    protected function getTemplateFile() {
        return $this->getTemplatesPath() . 'AccountSettings.xtpl';
    }

    protected function getOption($name) {
        if ($name === liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME && isset($_POST[liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME])) {
            return $_POST[liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME];
        }
        if ($name === liveagent_Settings::LA_OWNER_APIKEY && isset($_POST[liveagent_Settings::LA_OWNER_APIKEY])) {
            return $_POST[liveagent_Settings::LA_OWNER_APIKEY];
        }
        if ($name === liveagent_Settings::LA_URL_SETTING_NAME && isset($_POST[liveagent_Settings::LA_URL_SETTING_NAME])) {
            return $_POST[liveagent_Settings::LA_URL_SETTING_NAME];
        }
        return parent::getOption($name);
    }

    protected function initForm() {
        $this->addVariable('formActionURL', admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE));
        $this->addVariable('saveActionSettingsFlag', liveagent_Settings::SIGNUP_SETTINGS_PAGE_NAME);
        $this->addVariable(liveagent_Settings::LA_URL_SETTING_NAME, $this->settings->getLiveAgentUrl());
        $this->addVariable(liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME, $this->settings->getOwnerEmail());
        $this->addVariable(liveagent_Settings::LA_OWNER_APIKEY, $this->settings->getApiKey());

        $this->addVariable('dialogCaption', __('Live Chat & Help Desk for Wordpress', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('settingsSection', __('Connect to an existing account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('urlName', liveagent_Settings::LA_URL_SETTING_NAME);
        $this->addVariable('ownerEmailName', liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME);
        $this->addVariable('emailPlaceholder', __('Your admin/owner email', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('accountAPIKey', __('API v1 Key', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('apiKeyName', liveagent_Settings::LA_OWNER_APIKEY);

        $this->addVariable('connectCaption', __('Connect', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('contactLink', __('contact us', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('contactHelp', __('Do you need any help with this plugin? Feel free to', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('laOwnerEmailHelp', __('Username which you use to login to your LiveAgent'), LIVEAGENT_PLUGIN_NAME);
    }
}
