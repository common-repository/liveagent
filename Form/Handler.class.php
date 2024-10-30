<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

class liveagent_Form_Handler extends liveagent_Base {

    const TOP_LEVEL_OPTIONS_HANDLE = 'la-top-level-options-handle';

    /**
     * @var liveagent_Settings
     */
    private $settings;

    public function __construct(liveagent_Settings $settings) {
        $this->settings = $settings;
    }

    private function canPing($url, $apiKey) {
        try {
            $connectHelper = new liveagent_Helper_Connect();
            $connectHelper->ping($url, $apiKey);
            return true;
        } catch (liveagent_Exception_ConnectProblem $e) {
            return $e->getMessage();
        }
    }

    private function resetAccountSettings() {
        $this->settings->setOption(liveagent_Settings::ACCOUNT_STATUS, liveagent_Settings::ACCOUNT_STATUS_NOTSET);
        $this->settings->setOption(liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME, null);
        $this->settings->setOption(liveagent_Settings::LA_OWNER_APIKEY, null);
        $this->settings->setOption(liveagent_Settings::LA_OWNER_AUTHTOKEN, null);
        $this->settings->setOption(liveagent_Settings::LA_URL_SETTING_NAME, null);
        $this->settings->setOption(liveagent_Settings::BUTTON_CODE, null);
        $this->settings->setOption(liveagent_Settings::BUTTON_ID, null);
        $this->settings->setOption(liveagent_Settings::PREVIEW_BUTTON_IN_ADMIN, null);
        $this->settings->setOption(liveagent_Settings::ADDITIONAL_EMAIL, null);
        $this->settings->setOption(liveagent_Settings::ADDITIONAL_LEVEL, null);
        $this->settings->setOption(liveagent_Settings::ADDITIONAL_NAME, null);
        $this->settings->setOption(liveagent_Settings::CREATE_CUSTOMER, null);
    }

    private function saveAccountSettings() {
        $domain = $_POST[liveagent_Settings::LA_URL_SETTING_NAME];
        if ((strpos($domain, 'http:') === false) && (strpos($domain, 'https:') === false)) { // registration
            $domain = 'https://' . $domain . '.ladesk.com/';
            $authToken = $_POST[liveagent_Settings::LA_OWNER_AUTHTOKEN];
        } else { // account connect
            $authToken = '';
        }
        if (substr($domain, -1) !== '/') {
            $domain .= '/';
        }

        if ((strpos($domain, 'http:') !== false) && (strpos($domain, '.ladesk.com') !== false)) {
            $domain = str_replace('http:', 'https:', $domain);
        }
        $this->settings->setButtonCode(null);
        $this->settings->setButtonId(null);
        $this->settings->setOption(liveagent_Settings::ADDITIONAL_NAME, null);
        $this->settings->setOption(liveagent_Settings::ADDITIONAL_EMAIL, null);
        $this->settings->setOption(liveagent_Settings::ADDITIONAL_LEVEL, null);
        $this->settings->setOption(liveagent_Settings::CREATE_CUSTOMER, null);
        $this->settings->setOption(liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME, $_POST[liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME]);
        $this->settings->setOption(liveagent_Settings::LA_URL_SETTING_NAME, $domain);
        $this->settings->setOption(liveagent_Settings::LA_OWNER_APIKEY, $_POST[liveagent_Settings::LA_OWNER_APIKEY]);
        $this->settings->setOption(liveagent_Settings::PREVIEW_BUTTON_IN_ADMIN, 'Y');
        if ($authToken == '') {
            $authToken = $this->settings->getOwnerAuthToken();
        }
        $this->settings->setOption(liveagent_Settings::LA_OWNER_AUTHTOKEN, $authToken);
        $this->settings->setOption(liveagent_Settings::ACCOUNT_STATUS, liveagent_Settings::ACCOUNT_STATUS_SET);
    }

    private function handleButtonForm() {
        $this->settings->setButtonCode(stripslashes($_POST[liveagent_Settings::BUTTON_CODE]));
        $this->settings->setButtonId($_POST['buttonId']);

        // save additionals ...
        if (isset($_POST['displayInAdmin'])) {
            $this->settings->setOption(liveagent_Settings::PREVIEW_BUTTON_IN_ADMIN, $_POST['displayInAdmin']);
        } else {
            $this->settings->setOption(liveagent_Settings::PREVIEW_BUTTON_IN_ADMIN, '');
        }
        if (isset($_POST['configOptionName'])) {
            $this->settings->setOption(liveagent_Settings::ADDITIONAL_NAME, $_POST['configOptionName']);
        } else {
            $this->settings->setOption(liveagent_Settings::ADDITIONAL_NAME, '');
        }
        if (isset($_POST['configOptionEmail'])) {
            $this->settings->setOption(liveagent_Settings::ADDITIONAL_EMAIL, $_POST['configOptionEmail']);
        } else {
            $this->settings->setOption(liveagent_Settings::ADDITIONAL_EMAIL, '');
        }
        if (isset($_POST['configOptionLevel'])) {
            $this->settings->setOption(liveagent_Settings::ADDITIONAL_LEVEL, $_POST['configOptionLevel']);
        } else {
            $this->settings->setOption(liveagent_Settings::ADDITIONAL_LEVEL, '');
        }
        if (isset($_POST['laCreateCustomer'])) {
            $this->settings->setOption(liveagent_Settings::CREATE_CUSTOMER, $_POST['laCreateCustomer']);
        } else {
            $this->settings->setOption(liveagent_Settings::CREATE_CUSTOMER, '');
        }
    }

    public function printPrimaryPage() {
        if (isset($_POST['ac']) && ($_POST['ac'] === liveagent_Settings::ACTION_CREATE_ACCOUNT) && $_POST[liveagent_Settings::LA_OWNER_AUTHTOKEN] != '') {
            $this->settings->setOption(liveagent_Settings::ACCOUNT_STATUS, liveagent_Settings::ACCOUNT_STATUS_CREATING);
        }

        if (isset($_POST['option_page']) && ($_POST['option_page'] === liveagent_Settings::SIGNUP_SETTINGS_PAGE_NAME)) {
            $validator = new liveagent_Form_Validator_Account();
            $validator->setFields($_POST);
            $form = new liveagent_Form_Settings_Account($this->settings);
            if (!$validator->isValid()) {
                $form->setErrorMessages($validator->getErrors());
                $form->render();
                return;
            }
            $url = $_POST[liveagent_Settings::LA_URL_SETTING_NAME];
            if ((strpos($url, 'http:') !== false) && (strpos($url, '.ladesk.com') !== false)) {
                $url = str_replace('http:', 'https:', $url);
            }
            if (($e = $this->canPing($url, $_POST[liveagent_Settings::LA_OWNER_APIKEY])) !== true) {
                $form->setErrorMessages(array($e));
                $form->render();
                return;
            }
            $this->saveAccountSettings();
            $form = new liveagent_Form_Settings_ButtonCode($this->settings);
            $form->render();
            return;
        }

        if (isset($_POST['option_page']) && ($_POST['option_page'] === liveagent_Settings::BUTTONS_SETTINGS_PAGE_NAME)) {
            $this->handleButtonForm();
            $form = new liveagent_Form_Settings_ButtonCode($this->settings);
            if (isset($_POST['displayInAdmin'])) {
                $form->setInfoMessages(
                         array(__('Button code was saved successfully') . '. '.
                           __('The button loading might be blocked by your server - if the button is not displayed, click the') .
                           ' <a style="color: #ea7601" href="' . admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE) .
                           '">' . __('LiveAgent section again.') . '</a>'));
            } else {
                $form->setInfoMessages(array(__('Button code was saved successfully')));
            }
            $form->render();
            return;
        }

        if ($this->settings->getAccountStatus() === liveagent_Settings::ACCOUNT_STATUS_SET) {
            if (isset($_REQUEST['ac']) && $_REQUEST['ac'] === liveagent_Settings::ACTION_SKIP_CREATE) { // compatibility with old versions
                $form = new liveagent_Form_Settings_Account($this->settings);
                $form->render();
                return;
            }
            if (isset($_GET['ac']) && $_GET['ac'] === liveagent_Settings::ACTION_CHANGE_ACCOUNT) {
                $form = new liveagent_Form_Settings_Account($this->settings);
                $form->render();
                return;
            }
            if (isset($_GET['ac']) && $_GET['ac'] === liveagent_Settings::ACTION_RESET_ACCOUNT) {
                $this->resetAccountSettings();
                $form = new liveagent_Form_Signup($this->settings);
                $form->render();
                return;
            }
            if ($this->settings->getOwnerEmail() == null || $this->settings->getApiKey() == null ||
                    $this->settings->getLiveAgentUrl() == null) {
                $form = new liveagent_Form_Signup($this->settings);
                $form->render();
                return;
            }
            $form = new liveagent_Form_Settings_Account($this->settings);
            $ping = $this->canPing($this->settings->getLiveAgentUrl(), $this->settings->getApiKey());
            if ($ping !== true) {
                $form->setErrorMessages(array(__('Unable to connect to', LIVEAGENT_PLUGIN_NAME) . ' ' . $this->settings->getLiveAgentUrl() . ' Error: ' . $ping));
                $form->render();
                return;
            }
            $form = new liveagent_Form_Settings_ButtonCode($this->settings);
            $form->render();
            return;
        }

        if ($this->settings->getAccountStatus() === liveagent_Settings::ACCOUNT_STATUS_NOTSET ||
                $this->settings->getAccountStatus() == null ||
                $this->settings->getAccountStatus() === liveagent_Settings::ACCOUNT_STATUS_CREATING) {
            $this->handleAccountSignup();
        }
    }

    private function handleAccountSignup() {
        $form = new liveagent_Form_Signup($this->settings);
        if ($this->settings->getAccountStatus() === liveagent_Settings::ACCOUNT_STATUS_CREATING) {
            $this->saveAccountSettings();
            $form = new liveagent_Form_Settings_ButtonCode($this->settings);
            //$form->createDefaultWidget();
        }
        if (isset($_REQUEST['ac']) && $_REQUEST['ac'] === liveagent_Settings::ACTION_SKIP_CREATE) {
            $form = new liveagent_Form_Settings_Account($this->settings);
        }
        $form->render();
    }
}