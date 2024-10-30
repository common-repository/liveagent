<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

class liveagent_Settings {
    const CACHE_VALIDITY = 600;

    //internal settings
    const INTERNAL_SETTINGS = 'la-settings_internal-settings';
    const ACCOUNT_STATUS = 'la-settings_accountstatus';

    //general page
    const GENERAL_SETTINGS_PAGE_NAME = 'la-config-general-page';
    const SIGNUP_SETTINGS_PAGE_NAME = 'la-config-signup-page';
    const BUTTONS_SETTINGS_PAGE_NAME = 'la-config-buttons-page';

    const LA_FULL_NAME = 'la-full-name';
    const LA_URL_SETTING_NAME = 'la-url';
    const LA_OWNER_EMAIL_SETTING_NAME = 'la-owner-email';
    const LA_OWNER_APIKEY = 'la-owner-apikey';
    const LA_OWNER_AUTHTOKEN = 'la-owner-authtoken';

   	//additional widget options
	const ADDITIONAL_NAME = 'la-config-additional-name';
	const ADDITIONAL_EMAIL = 'la-config-additional-email';
	const ADDITIONAL_LEVEL = 'la-config-additional-level';
	const PREVIEW_BUTTON_IN_ADMIN = 'la-preview-button-in-admin';
	const CREATE_CUSTOMER = 'la-config-create-customer';

    //buttons options
    const BUTTON_CODE = 'la-buttons_buttoncode';
    const BUTTON_ID = 'la-config-button-id';

    const NO_AUTH_TOKEN = 'no_auth_token';

    //action codes
    const ACTION_CREATE_ACCOUNT = 'createAccount';
    const ACTION_SKIP_CREATE = 'skipCreate';
    const ACTION_CHANGE_ACCOUNT = 'changeAccount';
    const ACTION_RESET_ACCOUNT = 'resetAccount';

    //account statuses
    const ACCOUNT_STATUS_NOTSET = 'N';
    const ACCOUNT_STATUS_SET = 'S';
    const ACCOUNT_STATUS_CREATING = 'C';

    /**
     *
     */
    public function initSettingsForAdminPanel() {
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::LA_URL_SETTING_NAME, array($this, 'sanitizeUrl'));
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::LA_OWNER_EMAIL_SETTING_NAME);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::LA_OWNER_APIKEY);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::LA_OWNER_AUTHTOKEN);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::ADDITIONAL_NAME);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::ADDITIONAL_EMAIL);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::ADDITIONAL_LEVEL);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::CREATE_CUSTOMER);
        register_setting(self::GENERAL_SETTINGS_PAGE_NAME, self::PREVIEW_BUTTON_IN_ADMIN);
        register_setting(self::BUTTONS_SETTINGS_PAGE_NAME, self::BUTTON_CODE);
        register_setting(self::BUTTONS_SETTINGS_PAGE_NAME, self::BUTTON_ID);
        register_setting(self::INTERNAL_SETTINGS, self::ACCOUNT_STATUS);
    }

    /**
     * @return string
     */
    public function getAccountStatus() {
        if (get_option(self::ACCOUNT_STATUS) == '') {
            return self::ACCOUNT_STATUS_NOTSET;
        }
        return get_option(self::ACCOUNT_STATUS);
    }

    /**
     * @return bool
     */
    public function isAdminPreviewEnabled() {
        return (get_option(self::PREVIEW_BUTTON_IN_ADMIN) === 'Y');
    }

    /**
     * @param $url
     * @return string
     */
    public function sanitizeUrl($url) {
        if ($url == null) {
            return '';
        }
        if (stripos($url, 'http://')!==false || stripos($url, 'https://')!==false) {
            return esc_url($url);
        }
        return 'http://' . $url;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getOption($name) {
        return get_option($name);
    }

    /**
     * @param $code
     * @param $settingValue
     */
    public function setOption($code, $settingValue) {
        $settings = get_option($code);
        if ($settings != '') {
            update_option($code, $settingValue);
        } else {
            add_option($code, $settingValue);
            update_option($code, $settingValue);
        }
    }

    /**
     * @param $code
     * @param $value
     */
    private function setCachedSetting($code, $value) {
        $settings = get_option($code);
        $settingValue = $value . '||' . time();
        if ($settings != '') {
            update_option($code, $settingValue);
        } else {
            add_option($code, $settingValue);
            update_option($code, $settingValue);
        }
    }

    /**
     * @param $code
     * @return mixed
     * @throws liveagent_Exception_SettingNotValid
     */
    private function getCachedSetting($code) {
        $settings = get_option($code);
        if ($settings == null || trim($settings) == '') {
            throw new liveagent_Exception_SettingNotValid(__(sprintf('Setting %s not defined yet.', $code)));
        }
        $settings = explode('||', $settings, 2);

        if (!isset($settings[1])) {
            $message = __(sprintf('Setting\'s %s validity exceeded: time missing', $code));
            throw new liveagent_Exception_SettingNotValid($message);
        }

        $validTo = $settings[1] + self::CACHE_VALIDITY + 0;
        if ($settings[0] === self::NO_AUTH_TOKEN) {
            throw new liveagent_Exception_SettingNotValid('Empty');
        }
        if ($validTo > time()) {
            return $settings[0];
        } else {
            $message = __(sprintf('Setting\'s %s validity exceeded: %s', $code, $settings[1]));
            throw new liveagent_Exception_SettingNotValid($message);
        }
    }

    /**
     * @return mixed|string
     */
    public function getOwnerAuthToken() {
        try {
            return $this->getCachedSetting(self::LA_OWNER_AUTHTOKEN);
        } catch (liveagent_Exception_SettingNotValid $e) {
            //
        }
        try {
            $this->login();
            return $this->getCachedSetting(self::LA_OWNER_AUTHTOKEN);
        } catch (liveagent_Exception_SettingNotValid $e) {
            $this->setCachedSetting(self::LA_OWNER_AUTHTOKEN, self::NO_AUTH_TOKEN);
            return self::NO_AUTH_TOKEN;
        }
    }

    private function login() {
        try {
            $connectHelper = new liveagent_Helper_Connect();
            $loginData = $connectHelper->connectWithLA($this->getLiveAgentUrl(), $this->getOwnerEmail(), $this->getApiKey());
            $this->setCachedSetting(self::LA_OWNER_AUTHTOKEN, $loginData->authtoken);
        } catch (liveagent_Exception_ConnectProblem $e) {
            // we are communicating with older LA that does not send auth token
            $this->setCachedSetting(self::LA_OWNER_AUTHTOKEN, self::NO_AUTH_TOKEN);
        }
    }

    /**
     * @return string
     */
    public function createCustomer($contactParams) {
        $connectHelper = new liveagent_Helper_Connect();
        $customerResponse = $connectHelper->createCustomer($this->getLiveAgentUrl(), $contactParams);
        return $customerResponse->contactid;
    }

    public function getCustomer($email) {
        $connectHelper = new liveagent_Helper_Connect();
        try {
            $response = $connectHelper->getCustomer($this->getLiveAgentUrl(), $email, $this->getApiKey());
            return $response;
        } catch (liveagent_Exception_ConnectProblem $e) {
            return null;
        }
    }

    /**
     * @param $buttonCode
     */
    public function setButtonCode($buttonCode) {
        $this->setOption(self::BUTTON_CODE, $buttonCode);
    }

    /**
     * @param $buttonId
     */
    public function setButtonId($buttonId) {
        $this->setOption(self::BUTTON_ID, $buttonId);
    }

    /**
     * @return mixed
     */
    public function getLiveAgentUrl() {
        return get_option(self::LA_URL_SETTING_NAME);
    }

    /**
     * @return boolean
     */
    public function isSignupIntegrationEnabled() {
        if (get_option(self::CREATE_CUSTOMER) === '1') {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    private function getLogoURL() {
        return str_replace(array('http:', 'https:'), '', $this->getLiveAgentUrl()) . 'themes/install/_common_templates/img/default-contactwidget-logo.png';
    }

    /**
     * @return mixed
     */
    public function getOwnerEmail() {
        return get_option(self::LA_OWNER_EMAIL_SETTING_NAME);
    }

    /**
     * @return mixed
     */
    public function getApiKey() {
        return get_option(self::LA_OWNER_APIKEY);
    }

    /**
     * @return mixed
     */
    public function getButtonCode() {
        $code = get_option(self::BUTTON_CODE);
        if ($code != '') {
            return $code;
        }
    }

    /**
     * @param $htmlCode
     * @return mixed
     */
    public function replacePlaceholders($htmlCode) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
        } else {
            $htmlCode = str_replace('%%firstName%%', '', $htmlCode);
            $htmlCode = str_replace('%%lastName%%', '', $htmlCode);
            $htmlCode = str_replace('%%email%%', '', $htmlCode);
            $htmlCode = str_replace('%%level%%', '', $htmlCode);
            return $htmlCode;
        }

        if (($current_user->user_firstname != null) && ($current_user->user_firstname != '')) {
            $htmlCode = str_replace('%%firstName%%', "LiveAgent.addUserDetail('firstName', '" . $current_user->user_firstname . "');\n", $htmlCode);
        }
        else {
            $htmlCode = str_replace('%%firstName%%', '', $htmlCode);
        }

        if (($current_user->user_lastname != null) && ($current_user->user_lastname != '')) {
            $htmlCode = str_replace('%%lastName%%', "LiveAgent.addUserDetail('lastName', '" . $current_user->user_lastname . "');\n", $htmlCode);
        }
        else {
            $htmlCode = str_replace('%%lastName%%', '', $htmlCode);
        }

        if (($current_user->user_email != null) && ($current_user->user_email != '')) {
            $htmlCode = str_replace('%%email%%', "LiveAgent.addUserDetail('email', '" . $current_user->user_email . "');\n", $htmlCode);
        }
        else {
            $htmlCode = str_replace('%%email%%', '', $htmlCode);
        }

        if (($current_user->user_level != null) && ($current_user->user_level != '')) {
            $htmlCode = str_replace('%%level%%', "LiveAgent.addContactField('level', '" . $current_user->user_level . "');\n", $htmlCode);
        }
        else {
            $htmlCode = str_replace('%%level%%', '', $htmlCode);
        }
        return $htmlCode;
    }

    /**
     * @return string
     */
    public function getSavedButtonId() {
        if (get_option(self::BUTTON_ID) != '') {
            return get_option(self::BUTTON_ID);
        }
        return '';
    }

    /**
     * @return mixed
     * @throws liveagent_Exception_ConnectProblem
     */
    public function getAllWidgets() {
        $connectHelper = new liveagent_Helper_Connect();
        try {
            $widgetsList = $connectHelper->getWidgets($this->getLiveAgentUrl(), $this->getApiKey());
            return $widgetsList;
        } catch (liveagent_Exception_ConnectProblem $e) {
            throw new liveagent_Exception_ConnectProblem($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function getDefaultWidgetParams() {
        $attributes = array(
                array('section' => 'chat', 'name' => 'chat_action', 'value' => 'C'),
                array('section' => 'chat', 'name' => 'chat_not_available_action', 'value' => 'F'),
                array('section' => 'chat', 'name' => 'chat_max_queue', 'value' => '10'),
                array('section' => 'chat', 'name' => 'chat_type', 'value' => 'E'),
                array('section' => 'chat', 'name' => 'chat_window_height', 'value' => '450'),
                array('section' => 'chat', 'name' => 'chat_window_width', 'value' => '350'),
                array('section' => 'chat', 'name' => 'embedded_position', 'value' => 'BR'),
                array('section' => 'chat', 'name' => 'window_position', 'value' => 'C'),
                array('section' => 'chat', 'name' => 'chat_content_bg_color_from', 'value' => '4B1248'),
                array('section' => 'chat', 'name' => 'chat_content_bg_color_to', 'value' => 'F0C27B'),
                array('section' => 'chat', 'name' => 'chat_agentmessage_color', 'value' => '230323'),
                array('section' => 'chat', 'name' => 'chat_title', 'value' => 'Welcome'),
                array('section' => 'chat', 'name' => 'chat_visitormessage_color', 'value' => '9A7B98'),
                array('section' => 'chat', 'name' => 'chat_welcome_message', 'value' => ''),
                array('section' => 'chat', 'name' => 'leaving_mess_status', 'value' => 'Y'),
                array('section' => 'chat', 'name' => 'chat_window_zindex', 'value' => ''),
                array('section' => 'chat', 'name' => 'chat_design', 'value' => 'ascent'),
                array('section' => 'chat', 'name' => 'chat_custom_css', 'value' => ''),
                array('section' => 'button', 'name' => 'online_button_animation', 'value' => ''),
                array('section' => 'button', 'name' => 'online_button_position', 'value' => '75'),
                array('section' => 'button', 'name' => 'online_button_zindex', 'value' => ''),
                array('section' => 'button', 'name' => 'online_button_mobile', 'value' => ''),
                array('section' => 'button', 'name' => 'online_button_text', 'value' => 'Live Chat'),
                array('section' => 'button', 'name' => 'online_button_subtext', 'value' => 'we are online!'),
                array('section' => 'button', 'name' => 'online_button_hover', 'value' => ''),
                array('section' => 'button', 'name' => 'online_button_text_color', 'value' => '000000'),
                array('section' => 'button', 'name' => 'online_button_background_color', 'value' => 'FF9F10'),
                array('section' => 'contactForm', 'name' => 'online_form_title', 'value' => 'Welcome'),
                array('section' => 'contactForm', 'name' => 'online_form_description', 'value' => ''),
                array('section' => 'contactForm', 'name' => 'online_form_type', 'value' => 'E'),
                array('section' => 'contactForm', 'name' => 'online_form_embedded_position', 'value' => 'BR'),
                array('section' => 'contactForm', 'name' => 'online_form_window_position', 'value' => 'C'),
                array('section' => 'contactForm', 'name' => 'online_form_window_size', 'value' => 'M'),
                array('section' => 'contactForm', 'name' => 'online_kb_suggestions_treepath', 'value' => '0'),
                array('section' => 'contactForm', 'name' => 'online_kb_suggestions_kb_id', 'value' => 'kb_defa'),
                array('section' => 'contactForm', 'name' => 'online_show_kb_suggestions', 'value' => 'N'),
                array('section' => 'contactForm', 'name' => 'online_extend_kb_suggestions', 'value' => 'Y'),
                array('section' => 'contactForm', 'name' => 'online_kb_suggestions_parent_entry_id', 'value' => '0'),
                array('section' => 'contactForm', 'name' => 'online_form_confirm_message', 'value' => 'Please stand by, you will be redirected to operator shortly...'),
                array('section' => 'contactForm', 'name' => 'online_form_logourl', 'value' => $this->getLogoURL()),
                array('section' => 'contactForm', 'name' => 'online_form_window_height', 'value' => '450'),
                array('section' => 'contactForm', 'name' => 'online_form_window_width', 'value' => '500'),
                array('section' => 'contactForm', 'name' => 'online_form_blocker', 'value' => 'N'),
                array('section' => 'contactForm', 'name' => 'online_form_window_zindex', 'value' => ''),
                array('section' => 'contactForm', 'name' => 'online_contact_form_design', 'value' => 'ascent'),
                array('section' => 'contactForm', 'name' => 'online_form_content_bg_color_from', 'value' => '4B1248'),
                array('section' => 'contactForm', 'name' => 'online_form_content_bg_color_to', 'value' => 'F0C27B'),
                array('section' => 'contactForm', 'name' => 'online_form_custom_css', 'value' => ''),
                array('section' => 'contactForm', 'name' => 'online_form_department_choose', 'value' => 'N'),
                array('section' => 'button', 'name' => 'button_type', 'value' => '48'),
                array('section' => 'button', 'name' => 'offline_button_animation', 'value' => ''),
                array('section' => 'button', 'name' => 'offline_button_position', 'value' => '75'),
                array('section' => 'button', 'name' => 'offline_button_zindex', 'value' => ''),
                array('section' => 'button', 'name' => 'offline_button_mobile', 'value' => ''),
                array('section' => 'button', 'name' => 'offline_button_text', 'value' => 'Contact Us'),
                array('section' => 'button', 'name' => 'offline_button_subtext', 'value' => 'leave us a message'),
                array('section' => 'button', 'name' => 'offline_button_hover', 'value' => ''),
                array('section' => 'button', 'name' => 'offline_button_text_color', 'value' => '211D1D'),
                array('section' => 'button', 'name' => 'offline_button_background_color', 'value' => 'CFCFCF'),
                array('section' => 'contactForm', 'name' => 'form_title', 'value' => 'Welcome'),
                array('section' => 'contactForm', 'name' => 'show_kb_suggestions', 'value' => 'N'),
                array('section' => 'contactForm', 'name' => 'extend_kb_suggestions', 'value' => 'Y'),
                array('section' => 'contactForm', 'name' => 'kb_suggestions_kb_id', 'value' => 'kb_defa'),
                array('section' => 'contactForm', 'name' => 'kb_suggestions_parent_entry_id', 'value' => '0'),
                array('section' => 'contactForm', 'name' => 'kb_suggestions_treepath', 'value' => '0'),
                array('section' => 'contactForm', 'name' => 'form_description', 'value' => 'As we are not available now leave us a message'),
                array('section' => 'contactForm', 'name' => 'form_confirm_message', 'value' => 'Thanks for your question. We\'ll send you an answer via email to {$conversationOwnerEmail}'),
                array('section' => 'contactForm', 'name' => 'form_logourl', 'value' => $this->getLogoURL()),
                array('section' => 'contactForm', 'name' => 'form_type', 'value' => 'E'),
                array('section' => 'contactForm', 'name' => 'form_embedded_position', 'value' => 'C'),
                array('section' => 'contactForm', 'name' => 'form_window_position', 'value' => 'C'),
                array('section' => 'contactForm', 'name' => 'form_window_size', 'value' => 'A'),
                array('section' => 'contactForm', 'name' => 'form_window_height', 'value' => '450'),
                array('section' => 'contactForm', 'name' => 'form_window_width', 'value' => '450'),
                array('section' => 'contactForm', 'name' => 'form_blocker', 'value' => 'Y'),
                array('section' => 'contactForm', 'name' => 'form_window_zindex', 'value' => ''),
                array('section' => 'contactForm', 'name' => 'contact_form_design', 'value' => 'material'),
                array('section' => 'contactForm', 'name' => 'form_main_color_bg', 'value' => 'EFC50F'),
                array('section' => 'contactForm', 'name' => 'form_main_color_text', 'value' => 'FFFFFF'),
                array('section' => 'contactForm', 'name' => 'form_custom_css', 'value' => ''),
                array('section' => 'contactForm', 'name' => 'form_department_choose', 'value' => 'N')
        );

        $formFields = array(
            array('formid' => 'contactForm', 'name' => 'Name', 'rstatus' => 'M', 'code' => 'name', 'rtype' => 'T', 'availablevalues' => '', 'validator' => '', 'description' => ''),
            array('formid' => 'contactForm', 'name' => 'Email', 'rstatus' => 'M', 'code' => 'email', 'rtype' => 'T', 'availablevalues' => '', 'validator' => '', 'description' => ''),
            array('formid' => 'contactForm', 'name' => 'Message', 'rstatus' => 'M', 'code' => 'message', 'rtype' => 'M', 'availablevalues' => '', 'validator' => '', 'description' => ''),
            array('formid' => 'chat', 'name' => 'Name', 'rstatus' => 'M', 'code' => 'name', 'rtype' => 'T', 'availablevalues' => '', 'validator' => '', 'description' => ''),
            array('formid' => 'chat', 'name' => 'Email', 'rstatus' => 'M', 'code' => 'email', 'rtype' => 'T', 'availablevalues' => '', 'validator' => '', 'description' => '')
        );

        return array(
            'name' => 'Default button',
            'departmentid' => 'default',
            'language' => 'en-US',
            'provide' => 'BFC',
            'rtype' => 'C',
            'usecode' => 'B',
            'status' => 'A',
            'apikey' => $this->getApiKey(),
            'onlinecode' => '<div style="bottom: 0px;left: 50%; margin-left: -82px;-ms-transform-origin:50% 100%; -webkit-transform-origin:50% 100%; transform-origin:50% 100%; z-index: 999997; position: fixed;"><!-- SketchTextFloatingBottom --><div style="position:relative; white-space:nowrap; background:transparent; padding:5px 4px 25px;"><div style="cursor:pointer; bottom:4px; right:22px; position:absolute; width:0; height:0; border-style:solid; border-width:0 16px 21px 0; border-color: transparent #FFA621 transparent transparent;"></div><div style="bottom:0; right:20px; position:absolute; width:20px; height:25px; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAZCAYAAAAxFw7TAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAgFJREFUeNqslM9LVFEUx988Z8ZA0MERDREU1FlIaBCtXLpwUeBGsQRB3ImC7gIFCcI/QBdBUUSIG0WhTYsgcCMo4kIoCiJQkFGZyF/TDy2rz4Gv8HjNjO+pBz6ce+499/vOnHvnFtXX1lWXJxKv4Tm4ewf7i47PyCln/oeTw1iLse8JpGDN/by5kWb+PuzBGAk3/JvI+erkt2JogTR5J64mt+EBfIN1RJudgIZIFncMmxa7mjyFZwznNTeHaGMQQfIqcA1nsetbH4V3kIIXJCcDaH6HOMT+E6TKHVy/km5DUwBBE/sNyVwVmuiqfnpch5Q6p4f76p+bU1D2CJagHd4X6idrZTgjm1eQr37C3YOPEIUJNpbm0WxTz0sKVWiiW7hxhV3QnSf1j/xRQUGJzuFWFI5TZX2OtJ+wC7/OFZQNytfAS0SjvnW71JVwK5AgVa7hFhS2wl1fSgdEoDZohWZjsKPxLFXe8X5TPhZYkCrttJ96NnYiGld8qP7dDFOh2UMY0LjPRDU2sVM7bT7iRkIIOjqQad1Ruy49kIG38tdDCXpel4zCdeiFZZ120g0rSD+/4IYVtuhfUqTeVoQWlOgU7o3CEb3a9mJnLiQoe+y5m2al1uMLC1LNK9yMLrWj9/DaZSo0G/L8dLsBxZFLCjp6MD7oLlY5V2GITsJfG/8TYACG05OIiFquuAAAAABJRU5ErkJggg==);"></div><div style="cursor:pointer; background:#FFA621; padding:0 15px; font-family:Arial,Verdana,Helvetica,sans-serif;"><div style="position:absolute; height:79px; left:6px; right:4px; top:0; background-repeat:repeat-x; background-image:url(data:image/png;base64,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);"></div><div style="position:absolute; height:79px; width:7px; left:0; top:0; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABOCAYAAADsBQx3AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAm1JREFUeNp8lj9PVEEUxd8bngJhXUUJiwYBRTCIiolGrbSxsMXP4Iewt7cxsdFGG2JpqRQkhIYQYDUxMRp00RhdcaMLIruii+eSM5vx7h1vcnjh/ZiZ+2/uIx0eHCqsrJbKeHYkSXIX+gl9gOYz/NgGeIjnDSiXBOagTuiMBh5Woe+JYQIvQesxOA7VYlDAAfX+B3RR4CtoQsHfCG9BYBnqU7DNb/uG2+jjEoflDTyXFezahciOBL8S87YHehGDeeioBtgxdczpkLFwv8AxaMraVkp2DmqPnXkfemlCxCmeFk0IrwbwHIltuwNdj8GP0FWDNQTugz5pAl/WBfZDa7FtV6W4MXgeGo3B/v812CL0JAYHjO5rwoMMJ7SahxO+2wKre/gN6lUw9VDqOahibfZtG8OpB3DLw0f85W0ANz2c5VXftEI5zR7qjsUpem+dOQe9gx60xImiVvmXBehXy1WD3eb1dxYsslWyf85Ea7azKuLUts7tDjNUDlY2t83xZld8wkN4hJW5puPMGLysOGnV8zKnScO6n2Nc1RW8z3lYpMd7A/jVbzuJFM6q1H1pTk0kQsbNsQCOephyhFcCeAULMoF3oM9qIMtoz4lDF3jeiQCKg7WMY1xPsBKcrMm2j6FTCh7y4+2pTjjnYa/AW7oDaD0uduVhewTei8Dd7pO2fB67vGLTsdmXDxqrZeVx657A/jimLjVgh+NXtmTAw+LtBientjXvbcWAneJtIQLzjl9AC1b9x9WatxtS7NcMpa6+L90Zm+usVEGtfObYXJNWTeXFkr6XYcm2eG6LpfxW9tFr+TfkJltz5q8AAwAE5J0YHqNGIAAAAABJRU5ErkJggg==);"></div><div style="position:absolute; height:79px; width:7px; right:0; top:0; background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABPCAYAAAAnWd/SAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAlNJREFUeNqklk9LlFEUxmcur2kpjs30R8m0NIUik1pE0a5Frdu0CvoA7Qta9UVq2UaoECRcRiC0CYuioCINMk2ldFJHy6nnxHPszplz300Xnnln+HHvPefc59x3ioP9R6YL/8YP6Be/T2f4GC0kRijkjP+D49AV6KcHb0NFRhqPt+HD7MxrfDkL7TGwpntuQK0Gtih87sTTrnAKqqdSOQe9MaxToUT7yk5U+AnqNTBTeBwqG/hb4V7oi4HbCq/Lj1S096AXKXg/L88u6EIKnoT2p6AEM9wE4b4SnnMpJ2Qpu8hhL+O56EIsKw646hYBM9fxHIDWDCvqXu+gBwZuKBxxPFTLsKdEW4EOeKkchiTXLS+gj5xVMmxLUmnhnl2eTXaxYTMD12RZyW+Mh73p2eQpNA+teL0yRJU9KOkctQegQTyGFnjgPdYJL6Hz0BNvWSn8Z2h3E2Q6j6DTTQZDlSr0ba/nvkNQB1T19pQTmWQpm6D4px86YQ0W2H7H2OENReiE+pjOdoNN8LEKXWJw7RFslSOrs0KZWfZ7oMEu80zjsRK45ynomlxpEfwbaZFnuc/MvChQ2uEWq1OLYJ/6dpQuaIvgUkbv3GSe8XgfWNeHTtuXA5OfcLp7MPBqu+E0dkXgkpPGTiOJy+/mvXTG8uBX8VkKdjsvnQaD9aRukzljLhklhTPQrIEH1fHrzuU4H5ds2cAObYduVioebTqz5MDN2PEWflP3jTjX26LMfEb33TGwKrPOUIXUleqN1TxYD3zjVr1ldxqH98IA/0yIOxb+CDAA7p6Wli9ww8wAAAAASUVORK5CYII=);"></div><div style="color:#000000; font-size:28px; font-weight:bold; height:41px; padding-top:10px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">Live Chat</div><div style="color:#000000; font-size:14px; font-weight:bold; height:31px; padding-bottom:9px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">we are online!</div></div></div></div>',
            'onlinecode_css' => '@media print { #{$buttonid} { display:none}}   ',
            'onlinecode_ieold' => '<div style="bottom: 0px;left: 50%; margin-left: -82px;-ms-transform-origin:50% 100%; -webkit-transform-origin:50% 100%; transform-origin:50% 100%; z-index: 999997; position: fixed;"><!-- SketchTextFloatingBottom --><div style="position:relative; white-space:nowrap; background:transparent; padding:5px 4px 25px;"><div style="cursor:pointer; bottom:4px; right:22px; position:absolute; width:0; height:0; border-style:solid; border-width:0 16px 21px 0; border-color: transparent #FFA621 transparent transparent;"></div><div style="bottom:0; right:20px; position:absolute; width:20px; height:25px; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAZCAYAAAAxFw7TAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAgFJREFUeNqslM9LVFEUx988Z8ZA0MERDREU1FlIaBCtXLpwUeBGsQRB3ImC7gIFCcI/QBdBUUSIG0WhTYsgcCMo4kIoCiJQkFGZyF/TDy2rz4Gv8HjNjO+pBz6ce+499/vOnHvnFtXX1lWXJxKv4Tm4ewf7i47PyCln/oeTw1iLse8JpGDN/by5kWb+PuzBGAk3/JvI+erkt2JogTR5J64mt+EBfIN1RJudgIZIFncMmxa7mjyFZwznNTeHaGMQQfIqcA1nsetbH4V3kIIXJCcDaH6HOMT+E6TKHVy/km5DUwBBE/sNyVwVmuiqfnpch5Q6p4f76p+bU1D2CJagHd4X6idrZTgjm1eQr37C3YOPEIUJNpbm0WxTz0sKVWiiW7hxhV3QnSf1j/xRQUGJzuFWFI5TZX2OtJ+wC7/OFZQNytfAS0SjvnW71JVwK5AgVa7hFhS2wl1fSgdEoDZohWZjsKPxLFXe8X5TPhZYkCrttJ96NnYiGld8qP7dDFOh2UMY0LjPRDU2sVM7bT7iRkIIOjqQad1Ruy49kIG38tdDCXpel4zCdeiFZZ120g0rSD+/4IYVtuhfUqTeVoQWlOgU7o3CEb3a9mJnLiQoe+y5m2al1uMLC1LNK9yMLrWj9/DaZSo0G/L8dLsBxZFLCjp6MD7oLlY5V2GITsJfG/8TYACG05OIiFquuAAAAABJRU5ErkJggg==);"></div><div style="cursor:pointer; background:#FFA621; padding:0 15px; font-family:Arial,Verdana,Helvetica,sans-serif;"><div style="position:absolute; height:79px; left:6px; right:4px; top:0; background-repeat:repeat-x; background-image:url(data:image/png;base64,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);"></div><div style="position:absolute; height:79px; width:7px; left:0; top:0; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABOCAYAAADsBQx3AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAm1JREFUeNp8lj9PVEEUxd8bngJhXUUJiwYBRTCIiolGrbSxsMXP4Iewt7cxsdFGG2JpqRQkhIYQYDUxMRp00RhdcaMLIruii+eSM5vx7h1vcnjh/ZiZ+2/uIx0eHCqsrJbKeHYkSXIX+gl9gOYz/NgGeIjnDSiXBOagTuiMBh5Woe+JYQIvQesxOA7VYlDAAfX+B3RR4CtoQsHfCG9BYBnqU7DNb/uG2+jjEoflDTyXFezahciOBL8S87YHehGDeeioBtgxdczpkLFwv8AxaMraVkp2DmqPnXkfemlCxCmeFk0IrwbwHIltuwNdj8GP0FWDNQTugz5pAl/WBfZDa7FtV6W4MXgeGo3B/v812CL0JAYHjO5rwoMMJ7SahxO+2wKre/gN6lUw9VDqOahibfZtG8OpB3DLw0f85W0ANz2c5VXftEI5zR7qjsUpem+dOQe9gx60xImiVvmXBehXy1WD3eb1dxYsslWyf85Ea7azKuLUts7tDjNUDlY2t83xZld8wkN4hJW5puPMGLysOGnV8zKnScO6n2Nc1RW8z3lYpMd7A/jVbzuJFM6q1H1pTk0kQsbNsQCOephyhFcCeAULMoF3oM9qIMtoz4lDF3jeiQCKg7WMY1xPsBKcrMm2j6FTCh7y4+2pTjjnYa/AW7oDaD0uduVhewTei8Dd7pO2fB67vGLTsdmXDxqrZeVx657A/jimLjVgh+NXtmTAw+LtBientjXvbcWAneJtIQLzjl9AC1b9x9WatxtS7NcMpa6+L90Zm+usVEGtfObYXJNWTeXFkr6XYcm2eG6LpfxW9tFr+TfkJltz5q8AAwAE5J0YHqNGIAAAAABJRU5ErkJggg==);"></div><div style="position:absolute; height:79px; width:7px; right:0; top:0; background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABPCAYAAAAnWd/SAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAlNJREFUeNqklk9LlFEUxmcur2kpjs30R8m0NIUik1pE0a5Frdu0CvoA7Qta9UVq2UaoECRcRiC0CYuioCINMk2ldFJHy6nnxHPszplz300Xnnln+HHvPefc59x3ioP9R6YL/8YP6Be/T2f4GC0kRijkjP+D49AV6KcHb0NFRhqPt+HD7MxrfDkL7TGwpntuQK0Gtih87sTTrnAKqqdSOQe9MaxToUT7yk5U+AnqNTBTeBwqG/hb4V7oi4HbCq/Lj1S096AXKXg/L88u6EIKnoT2p6AEM9wE4b4SnnMpJ2Qpu8hhL+O56EIsKw646hYBM9fxHIDWDCvqXu+gBwZuKBxxPFTLsKdEW4EOeKkchiTXLS+gj5xVMmxLUmnhnl2eTXaxYTMD12RZyW+Mh73p2eQpNA+teL0yRJU9KOkctQegQTyGFnjgPdYJL6Hz0BNvWSn8Z2h3E2Q6j6DTTQZDlSr0ba/nvkNQB1T19pQTmWQpm6D4px86YQ0W2H7H2OENReiE+pjOdoNN8LEKXWJw7RFslSOrs0KZWfZ7oMEu80zjsRK45ynomlxpEfwbaZFnuc/MvChQ2uEWq1OLYJ/6dpQuaIvgUkbv3GSe8XgfWNeHTtuXA5OfcLp7MPBqu+E0dkXgkpPGTiOJy+/mvXTG8uBX8VkKdjsvnQaD9aRukzljLhklhTPQrIEH1fHrzuU4H5ds2cAObYduVioebTqz5MDN2PEWflP3jTjX26LMfEb33TGwKrPOUIXUleqN1TxYD3zjVr1ldxqH98IA/0yIOxb+CDAA7p6Wli9ww8wAAAAASUVORK5CYII=);"></div><div style="color:#000000; font-size:28px; font-weight:bold; height:41px; padding-top:10px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">Live Chat</div><div style="color:#000000; font-size:14px; font-weight:bold; height:31px; padding-bottom:9px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">we are online!</div></div></div></div>',
            'onlinecode_ieold_css' => '@media print { #{$buttonid} { display:none}}   ',
            'offlinecode' => '<div style="bottom: 0px;left: 50%; margin-left: -93px;-ms-transform-origin:50% 100%; -webkit-transform-origin:50% 100%; transform-origin:50% 100%; z-index: 999997; position: fixed;"><!-- SketchTextFloatingBottom --><div style="position:relative; white-space:nowrap; background:transparent; padding:5px 4px 25px;"><div style="cursor:pointer; bottom:4px; right:22px; position:absolute; width:0; height:0; border-style:solid; border-width:0 16px 21px 0; border-color: transparent #CFCFCF transparent transparent;"></div><div style="bottom:0; right:20px; position:absolute; width:20px; height:25px; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAZCAYAAAAxFw7TAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAgFJREFUeNqslM9LVFEUx988Z8ZA0MERDREU1FlIaBCtXLpwUeBGsQRB3ImC7gIFCcI/QBdBUUSIG0WhTYsgcCMo4kIoCiJQkFGZyF/TDy2rz4Gv8HjNjO+pBz6ce+499/vOnHvnFtXX1lWXJxKv4Tm4ewf7i47PyCln/oeTw1iLse8JpGDN/by5kWb+PuzBGAk3/JvI+erkt2JogTR5J64mt+EBfIN1RJudgIZIFncMmxa7mjyFZwznNTeHaGMQQfIqcA1nsetbH4V3kIIXJCcDaH6HOMT+E6TKHVy/km5DUwBBE/sNyVwVmuiqfnpch5Q6p4f76p+bU1D2CJagHd4X6idrZTgjm1eQr37C3YOPEIUJNpbm0WxTz0sKVWiiW7hxhV3QnSf1j/xRQUGJzuFWFI5TZX2OtJ+wC7/OFZQNytfAS0SjvnW71JVwK5AgVa7hFhS2wl1fSgdEoDZohWZjsKPxLFXe8X5TPhZYkCrttJ96NnYiGld8qP7dDFOh2UMY0LjPRDU2sVM7bT7iRkIIOjqQad1Ruy49kIG38tdDCXpel4zCdeiFZZ120g0rSD+/4IYVtuhfUqTeVoQWlOgU7o3CEb3a9mJnLiQoe+y5m2al1uMLC1LNK9yMLrWj9/DaZSo0G/L8dLsBxZFLCjp6MD7oLlY5V2GITsJfG/8TYACG05OIiFquuAAAAABJRU5ErkJggg==);"></div><div style="cursor:pointer; background:#CFCFCF; padding:0 15px; font-family:Arial,Verdana,Helvetica,sans-serif;"><div style="position:absolute; height:79px; left:6px; right:4px; top:0; background-repeat:repeat-x; background-image:url(data:image/png;base64,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);"></div><div style="position:absolute; height:79px; width:7px; left:0; top:0; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABOCAYAAADsBQx3AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAm1JREFUeNp8lj9PVEEUxd8bngJhXUUJiwYBRTCIiolGrbSxsMXP4Iewt7cxsdFGG2JpqRQkhIYQYDUxMRp00RhdcaMLIruii+eSM5vx7h1vcnjh/ZiZ+2/uIx0eHCqsrJbKeHYkSXIX+gl9gOYz/NgGeIjnDSiXBOagTuiMBh5Woe+JYQIvQesxOA7VYlDAAfX+B3RR4CtoQsHfCG9BYBnqU7DNb/uG2+jjEoflDTyXFezahciOBL8S87YHehGDeeioBtgxdczpkLFwv8AxaMraVkp2DmqPnXkfemlCxCmeFk0IrwbwHIltuwNdj8GP0FWDNQTugz5pAl/WBfZDa7FtV6W4MXgeGo3B/v812CL0JAYHjO5rwoMMJ7SahxO+2wKre/gN6lUw9VDqOahibfZtG8OpB3DLw0f85W0ANz2c5VXftEI5zR7qjsUpem+dOQe9gx60xImiVvmXBehXy1WD3eb1dxYsslWyf85Ea7azKuLUts7tDjNUDlY2t83xZld8wkN4hJW5puPMGLysOGnV8zKnScO6n2Nc1RW8z3lYpMd7A/jVbzuJFM6q1H1pTk0kQsbNsQCOephyhFcCeAULMoF3oM9qIMtoz4lDF3jeiQCKg7WMY1xPsBKcrMm2j6FTCh7y4+2pTjjnYa/AW7oDaD0uduVhewTei8Dd7pO2fB67vGLTsdmXDxqrZeVx657A/jimLjVgh+NXtmTAw+LtBientjXvbcWAneJtIQLzjl9AC1b9x9WatxtS7NcMpa6+L90Zm+usVEGtfObYXJNWTeXFkr6XYcm2eG6LpfxW9tFr+TfkJltz5q8AAwAE5J0YHqNGIAAAAABJRU5ErkJggg==);"></div><div style="position:absolute; height:79px; width:7px; right:0; top:0; background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABPCAYAAAAnWd/SAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAlNJREFUeNqklk9LlFEUxmcur2kpjs30R8m0NIUik1pE0a5Frdu0CvoA7Qta9UVq2UaoECRcRiC0CYuioCINMk2ldFJHy6nnxHPszplz300Xnnln+HHvPefc59x3ioP9R6YL/8YP6Be/T2f4GC0kRijkjP+D49AV6KcHb0NFRhqPt+HD7MxrfDkL7TGwpntuQK0Gtih87sTTrnAKqqdSOQe9MaxToUT7yk5U+AnqNTBTeBwqG/hb4V7oi4HbCq/Lj1S096AXKXg/L88u6EIKnoT2p6AEM9wE4b4SnnMpJ2Qpu8hhL+O56EIsKw646hYBM9fxHIDWDCvqXu+gBwZuKBxxPFTLsKdEW4EOeKkchiTXLS+gj5xVMmxLUmnhnl2eTXaxYTMD12RZyW+Mh73p2eQpNA+teL0yRJU9KOkctQegQTyGFnjgPdYJL6Hz0BNvWSn8Z2h3E2Q6j6DTTQZDlSr0ba/nvkNQB1T19pQTmWQpm6D4px86YQ0W2H7H2OENReiE+pjOdoNN8LEKXWJw7RFslSOrs0KZWfZ7oMEu80zjsRK45ynomlxpEfwbaZFnuc/MvChQ2uEWq1OLYJ/6dpQuaIvgUkbv3GSe8XgfWNeHTtuXA5OfcLp7MPBqu+E0dkXgkpPGTiOJy+/mvXTG8uBX8VkKdjsvnQaD9aRukzljLhklhTPQrIEH1fHrzuU4H5ds2cAObYduVioebTqz5MDN2PEWflP3jTjX26LMfEb33TGwKrPOUIXUleqN1TxYD3zjVr1ldxqH98IA/0yIOxb+CDAA7p6Wli9ww8wAAAAASUVORK5CYII=);"></div><div style="color:#211D1D; font-size:28px; font-weight:bold; height:41px; padding-top:10px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">Contact Us</div><div style="color:#211D1D; font-size:14px; font-weight:bold; height:31px; padding-bottom:9px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">leave us a message</div></div></div></div>',
            'offlinecode_ieold' => '<div style="bottom: 0px;left: 50%; margin-left: -93px;-ms-transform-origin:50% 100%; -webkit-transform-origin:50% 100%; transform-origin:50% 100%; z-index: 999997; position: fixed;"><!-- SketchTextFloatingBottom --><div style="position:relative; white-space:nowrap; background:transparent; padding:5px 4px 25px;"><div style="cursor:pointer; bottom:4px; right:22px; position:absolute; width:0; height:0; border-style:solid; border-width:0 16px 21px 0; border-color: transparent #CFCFCF transparent transparent;"></div><div style="bottom:0; right:20px; position:absolute; width:20px; height:25px; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAZCAYAAAAxFw7TAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAgFJREFUeNqslM9LVFEUx988Z8ZA0MERDREU1FlIaBCtXLpwUeBGsQRB3ImC7gIFCcI/QBdBUUSIG0WhTYsgcCMo4kIoCiJQkFGZyF/TDy2rz4Gv8HjNjO+pBz6ce+499/vOnHvnFtXX1lWXJxKv4Tm4ewf7i47PyCln/oeTw1iLse8JpGDN/by5kWb+PuzBGAk3/JvI+erkt2JogTR5J64mt+EBfIN1RJudgIZIFncMmxa7mjyFZwznNTeHaGMQQfIqcA1nsetbH4V3kIIXJCcDaH6HOMT+E6TKHVy/km5DUwBBE/sNyVwVmuiqfnpch5Q6p4f76p+bU1D2CJagHd4X6idrZTgjm1eQr37C3YOPEIUJNpbm0WxTz0sKVWiiW7hxhV3QnSf1j/xRQUGJzuFWFI5TZX2OtJ+wC7/OFZQNytfAS0SjvnW71JVwK5AgVa7hFhS2wl1fSgdEoDZohWZjsKPxLFXe8X5TPhZYkCrttJ96NnYiGld8qP7dDFOh2UMY0LjPRDU2sVM7bT7iRkIIOjqQad1Ruy49kIG38tdDCXpel4zCdeiFZZ120g0rSD+/4IYVtuhfUqTeVoQWlOgU7o3CEb3a9mJnLiQoe+y5m2al1uMLC1LNK9yMLrWj9/DaZSo0G/L8dLsBxZFLCjp6MD7oLlY5V2GITsJfG/8TYACG05OIiFquuAAAAABJRU5ErkJggg==);"></div><div style="cursor:pointer; background:#CFCFCF; padding:0 15px; font-family:Arial,Verdana,Helvetica,sans-serif;"><div style="position:absolute; height:79px; left:6px; right:4px; top:0; background-repeat:repeat-x; background-image:url(data:image/png;base64,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);"></div><div style="position:absolute; height:79px; width:7px; left:0; top:0; background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABOCAYAAADsBQx3AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAm1JREFUeNp8lj9PVEEUxd8bngJhXUUJiwYBRTCIiolGrbSxsMXP4Iewt7cxsdFGG2JpqRQkhIYQYDUxMRp00RhdcaMLIruii+eSM5vx7h1vcnjh/ZiZ+2/uIx0eHCqsrJbKeHYkSXIX+gl9gOYz/NgGeIjnDSiXBOagTuiMBh5Woe+JYQIvQesxOA7VYlDAAfX+B3RR4CtoQsHfCG9BYBnqU7DNb/uG2+jjEoflDTyXFezahciOBL8S87YHehGDeeioBtgxdczpkLFwv8AxaMraVkp2DmqPnXkfemlCxCmeFk0IrwbwHIltuwNdj8GP0FWDNQTugz5pAl/WBfZDa7FtV6W4MXgeGo3B/v812CL0JAYHjO5rwoMMJ7SahxO+2wKre/gN6lUw9VDqOahibfZtG8OpB3DLw0f85W0ANz2c5VXftEI5zR7qjsUpem+dOQe9gx60xImiVvmXBehXy1WD3eb1dxYsslWyf85Ea7azKuLUts7tDjNUDlY2t83xZld8wkN4hJW5puPMGLysOGnV8zKnScO6n2Nc1RW8z3lYpMd7A/jVbzuJFM6q1H1pTk0kQsbNsQCOephyhFcCeAULMoF3oM9qIMtoz4lDF3jeiQCKg7WMY1xPsBKcrMm2j6FTCh7y4+2pTjjnYa/AW7oDaD0uduVhewTei8Dd7pO2fB67vGLTsdmXDxqrZeVx657A/jimLjVgh+NXtmTAw+LtBientjXvbcWAneJtIQLzjl9AC1b9x9WatxtS7NcMpa6+L90Zm+usVEGtfObYXJNWTeXFkr6XYcm2eG6LpfxW9tFr+TfkJltz5q8AAwAE5J0YHqNGIAAAAABJRU5ErkJggg==);"></div><div style="position:absolute; height:79px; width:7px; right:0; top:0; background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAcAAABPCAYAAAAnWd/SAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAlNJREFUeNqklk9LlFEUxmcur2kpjs30R8m0NIUik1pE0a5Frdu0CvoA7Qta9UVq2UaoECRcRiC0CYuioCINMk2ldFJHy6nnxHPszplz300Xnnln+HHvPefc59x3ioP9R6YL/8YP6Be/T2f4GC0kRijkjP+D49AV6KcHb0NFRhqPt+HD7MxrfDkL7TGwpntuQK0Gtih87sTTrnAKqqdSOQe9MaxToUT7yk5U+AnqNTBTeBwqG/hb4V7oi4HbCq/Lj1S096AXKXg/L88u6EIKnoT2p6AEM9wE4b4SnnMpJ2Qpu8hhL+O56EIsKw646hYBM9fxHIDWDCvqXu+gBwZuKBxxPFTLsKdEW4EOeKkchiTXLS+gj5xVMmxLUmnhnl2eTXaxYTMD12RZyW+Mh73p2eQpNA+teL0yRJU9KOkctQegQTyGFnjgPdYJL6Hz0BNvWSn8Z2h3E2Q6j6DTTQZDlSr0ba/nvkNQB1T19pQTmWQpm6D4px86YQ0W2H7H2OENReiE+pjOdoNN8LEKXWJw7RFslSOrs0KZWfZ7oMEu80zjsRK45ynomlxpEfwbaZFnuc/MvChQ2uEWq1OLYJ/6dpQuaIvgUkbv3GSe8XgfWNeHTtuXA5OfcLp7MPBqu+E0dkXgkpPGTiOJy+/mvXTG8uBX8VkKdjsvnQaD9aRukzljLhklhTPQrIEH1fHrzuU4H5ds2cAObYduVioebTqz5MDN2PEWflP3jTjX26LMfEb33TGwKrPOUIXUleqN1TxYD3zjVr1ldxqH98IA/0yIOxb+CDAA7p6Wli9ww8wAAAAASUVORK5CYII=);"></div><div style="color:#211D1D; font-size:28px; font-weight:bold; height:41px; padding-top:10px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">Contact Us</div><div style="color:#211D1D; font-size:14px; font-weight:bold; height:31px; padding-bottom:9px; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;">leave us a message</div></div></div></div>',
            'offlinecode_css' => '@media print { #{$buttonid} { display:none}}   ',
            'offlinecode_ieold_css' => '@media print { #{$buttonid} { display:none}}   ',
            'attributes' => json_encode($attributes),
            'form_fields' => json_encode($formFields)
        );
    }
}