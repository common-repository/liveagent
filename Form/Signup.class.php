<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

class liveagent_Form_Signup extends liveagent_Form_Base {
    private $userName = '';

    public function __construct(liveagent_Settings $settings) {
        $current_user = wp_get_current_user();
        $this->userName = $current_user->user_firstname . ' ' . $current_user->user_lastname;
        if ($this->userName === ' ') {
            $this->userName = $current_user->display_name;
        }
        if ($this->userName == '') {
            $this->userName = $current_user->user_login;
        }
        parent::__construct($settings);
        if (isset($_POST['la-error-message'])) {
            $this->setErrorMessages(array($_POST['la-error-message']));
        }
    }

    protected function getTemplateFile() {
        return $this->getTemplatesPath() . 'AccountSignup.xtpl';
    }

    private function getdomainOnly() {
        $fullDomain = @$_SERVER['SERVER_NAME'];

        while (preg_match('/^([A-Za-z0-9-_]+\.)+([A-Za-z]{2,6})$/', $fullDomain)) {
            $domainSuffix = preg_replace('/^([A-Za-z0-9-_]+\.)+([A-Za-z]{2,6})$/', '$2', $fullDomain);
            $fullDomain = str_replace('.' . $domainSuffix, '', $fullDomain);
        }
        $domain = str_replace('www.', '', $fullDomain);
        $domain = str_replace('.', '-', $domain);

        if (trim($domain) === 'localhost') {
            return '';
        }
        if (preg_match('/^[a-zA-Z0-9-_]+$/', $domain) === false) {
            return '';
        }
        return $domain;
    }

    protected function getOption($name) {
        switch ($name) {
            case liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME:
                if (isset($_POST[liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME]) && $_POST[liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME] != '') {
                    return $_POST[liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME];
                }
                return get_bloginfo('admin_email');
                break;
            case liveagent_Settings::LA_FULL_NAME:
                if (isset($_POST[liveagent_Settings::LA_FULL_NAME]) && $_POST[liveagent_Settings::LA_FULL_NAME] != '') {
                    return $_POST[liveagent_Settings::LA_FULL_NAME];
                }
                return $this->userName;
                break;
            case liveagent_Settings::LA_URL_SETTING_NAME:
                if (isset($_POST[liveagent_Settings::LA_URL_SETTING_NAME]) && $_POST[liveagent_Settings::LA_URL_SETTING_NAME] != '') {
                    return $_POST[liveagent_Settings::LA_URL_SETTING_NAME];
                }
                return $this->getdomainOnly();
                break;
        }
    }

    protected function initForm() {
        $this->addVariable('getStarted', __('Live Chat & Help Desk for Wordpress', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('dialogCaption', __('Create Free Account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('dialogSubcaption', __('Enjoy Forever Free Account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('submitCaption', __('Create account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('fullNameLabel', __('Full name', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('emailPlaceholder', __('Enter your e-mail', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('accountNameLabel', __('Your account name', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('domainPlaceholder',  __('.ladesk.com', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('free',  __('for FREE', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('productUpdatesCheck',  __('Send me product updates and other promotional offers.', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('accountInfo', __('Choose a name for your LiveAgent subdomain. Most people use their company or team name.', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('IAgree', __('By signing up, I accept', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('TnC', __('T&C', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('pp', __('Privacy Policy', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('and', __('and', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('skipLink', __('Skip this step, I already have an account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('buildingLA', __('Building Your Account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('buildingLALong', __('Your account is being created. Your login information will be sent to your email address.', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('whatsNew', __('Meanwhile, you can check what\'s new on', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('loading', __('Loading', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('blog', __('Our blog', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('howItWorks', __('See How Live Chat Works', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('settingsSection', __('Create Free Account', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('pluginHelpText', __('Improve your customer interactions with forever free LiveAgent plan. With unlimited free live chats, agents, emails, calls & contact forms you will be ready to keep your customers always happy and loyal. Signup takes less than 60 seconds. Enjoy your forever free account!', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('pluginHelpText2', __('Our free live chat app boasts the fastest chat widget on the market which is fully customizable, as well as language adaptable.', LIVEAGENT_PLUGIN_NAME));
        $this->addVariable('skipCreateUrl', admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE . '&ac=' . liveagent_Settings::ACTION_SKIP_CREATE));
        $this->addVariable('registerUrlAction', admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE));

        $this->addVariable(liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME, $this->getOption(liveagent_Settings::LA_OWNER_EMAIL_SETTING_NAME));
        $this->addVariable(liveagent_Settings::LA_FULL_NAME, $this->getOption(liveagent_Settings::LA_FULL_NAME));
        $this->addVariable(liveagent_Settings::LA_URL_SETTING_NAME, $this->getOption(liveagent_Settings::LA_URL_SETTING_NAME));
    }
}