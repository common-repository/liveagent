<?php
/*
 Plugin Name: LiveAgent
 Plugin URI: http://www.qualityunit.com/liveagent
 Description: Plugin enables integration of Wordpress with LiveAgent
 Author: QualityUnit
 Version: 4.4.5
 Author URI: http://www.qualityunit.com/
 License: GPL3 - http://gplv3.fsf.org/
 */
if (!defined('LIVEAGENT_PLUGIN_VERSION')) {
    define('LIVEAGENT_PLUGIN_VERSION', '4.4.5');
}
if (!defined('LIVEAGENT_PLUGIN_NAME')) {
    define('LIVEAGENT_PLUGIN_NAME', 'liveagent');
}

include_once WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . LIVEAGENT_PLUGIN_NAME . DIRECTORY_SEPARATOR . 'Config.php';

$liveagentloadErrorMessage = null;
if (!function_exists('liveagent_PluginLoadError')) {

    function liveagent_PluginLoadError() {
        global $liveagentloadErrorMessage;
        if (current_user_can('install_plugins') && current_user_can('manage_options')) {
            echo '<div class="error"><p>' . $liveagentloadErrorMessage . '</p></div>';
        }
    }
}

try {
    include_once WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . LIVEAGENT_PLUGIN_NAME . DIRECTORY_SEPARATOR . 'Loader.php';
    $liveagentLoader = liveagent_Loader::getInstance();
    $liveagentLoader->load();
} catch (Exception $e) {
    $liveagentloadErrorMessage = sprintf(__('Critical error during %s plugin load %s', LIVEAGENT_PLUGIN_NAME), LIVEAGENT_PLUGIN_NAME, $e->getMessage());
    add_action('admin_notices', 'liveagent_PluginLoadError');
    return;
}

if (!class_exists(LIVEAGENT_PLUGIN_NAME)) {
    class liveagent extends liveagent_Base {

        /**
         * @var liveagent
         */
        private static $instance = null;

        /**
         * @var liveagent_Settings
         */
        private $settings;

        /**
         * @var boolean
         */
        private $active = false;

        public function activate() {
            if ($this->isActive()) {
                return;
            }
            $this->settings = new liveagent_Settings();

            $this->initPlugin();
            $this->active = true;
        }

        public function isActive() {
            return $this->active;
        }

        public static function getInstance() {
            if (self::$instance == null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function adminInit() {
            $this->settings->initSettingsForAdminPanel();
        }

        private function accountIsValid() {
            return $this->settings->getAccountStatus() != liveagent_Settings::ACCOUNT_STATUS_NOTSET && $this->settings->getAccountStatus() != liveagent_Settings::ACCOUNT_STATUS_CREATING && $this->settings->getOwnerEmail() != '' && $this->settings->getApiKey() != '' && $this->settings->getLiveAgentUrl() != '';
        }

        private function initFrontend() {
            if (!$this->accountIsValid()) {
                return;
            }
            add_filter('wp_footer', array(
                    $this,
                    'initFooter'
            ), 99);
        }

        private function initPlugin() {
            add_action('admin_init', array(
                    $this,
                    'adminInit'
            ));
            add_action('user_register', array(
                    $this,
                    'onNewUserRegistration'
            ), 99);
            add_action('mgm_user_register', array(
                    $this,
                    'onNewUserRegistration'
            ), 99); //fix to work with magic members
            add_action('woocommerce_created_customer', array(
                    $this,
                    'onNewUserRegistrationWoo'
            ), 99, 3); //fix to work with WooCommerce registration
            add_shortcode('customerPortalLogin', array($this, 'getCustomerLoginLinkForShortCode'));

            if (!is_admin()) {
                $this->initFrontend();
                return;
            }
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                    $this,
                    'addSettingsLinkIntoPlugin'
            ));
            add_action('admin_menu', array(
                    $this,
                    'addPrimaryConfigMenu'
            ));
            add_filter('admin_head', array(
                    $this,
                    'initAdminHeader'
            ), 99);
            add_action('wp_enqueue_scripts', array(
                    $this,
                    'includeJavascripts'
            ));
            add_action('admin_enqueue_scripts', array(
                    $this,
                    'includeJavascripts'
            ));
            add_action('in_admin_footer', array(
                    $this,
                    'addButtonToAdminFooter'
            ));
            add_action('plugins_loaded', array(
                    $this,
                    'loadTranslations'
            ));
        }

        // [customerPortalLogin caption="Login to your customer portal"]
        public function getCustomerLoginLinkForShortCode($attributes) {
            global $current_user;
            if ($current_user->ID == 0 || $current_user->user_email == '') {
                return;
            }

            $apikey = $this->settings->getApiKey();
            if ($apikey === '') {
                $this->_log(__('API v1 key is missing! Shortcode cannot be processed.'));
                return;
            }

            $customerObject = $this->settings->getCustomer($current_user->user_email);

            if ($customerObject == null || $customerObject->authtoken == null || $customerObject->authtoken == '') {
                $this->_log(__('The needed AuthToken for this customer does not exist. The customer is probably new and has not set their password yet.'));
                return;
            }

            $loginLink = $this->settings->getLiveAgentUrl() . 'login';
            $loginAuthToken = $customerObject->authtoken;

            if (empty($loginAuthToken)) {
                return;
            }

            $caption = 'Customer portal login';
            if (isset($attributes['caption'])) {
                $caption = $attributes['caption'];
            }

            return "<form id='redirectForm' name='redirectForm' action='$loginLink' method='post'>
                <input type='hidden' name='AuthToken' value='$loginAuthToken'>
                <input type='submit' name='submit' value='$caption'>
            </form>";
        }

        private function importTranslationsToJavascript() {
            $translation_array = array(
                    'installing' => __('Installing', LIVEAGENT_PLUGIN_NAME),
                    'justFewMoreSeconds' => __('Just a few more seconds', LIVEAGENT_PLUGIN_NAME),
                    'completing' => __('Installation completed. Setting up...', LIVEAGENT_PLUGIN_NAME),
                    'youSureResetAccount' => __('Are you sure you want to cancel your account?', LIVEAGENT_PLUGIN_NAME)
            );
            wp_localize_script('liveagent-main', 'liveagentLocalizations', $translation_array);
        }

        public function includeJavascripts() {
            wp_enqueue_script('liveagent-alphanum', $this->getJsUrl() . 'jquery.alphanum.js', array(), LIVEAGENT_PLUGIN_VERSION);
            wp_enqueue_script('liveagent-la', $this->getJsUrl() . 'lasignup.js', array(), LIVEAGENT_PLUGIN_VERSION);
            wp_enqueue_script('liveagent-lacrm', $this->getJsUrl() . 'crm_lasignup.js', array(), LIVEAGENT_PLUGIN_VERSION);

            $this->importTranslationsToJavascript();
        }

        public function initAdminHeader($content) {
            if (!is_feed()) {
                if (isset($_GET['page']) && ($_GET['page'] == 'la-top-level-options-handle')) {
                    echo $this->getStylesheetHeaderLink('style.css');
                    echo $this->getStylesheetHeaderLink('animation.css');
                    echo $this->getStylesheetHeaderLink('responsive.css');
                }
            }
            echo $content;
        }

        public function addSettingsLinkIntoPlugin($links) {
            return array_merge($links, array(
                    '<a href="' . admin_url('admin.php?page=' . liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE) . '">Settings</a>'
            ));
        }

        public function loadTranslations() {
            load_plugin_textdomain(LIVEAGENT_PLUGIN_NAME, false, dirname(plugin_basename(__FILE__)) . '/resources/languages/');
        }

        public function addButtonToAdminFooter($content) {
            if (!is_feed()) {
                if ($this->settings->isAdminPreviewEnabled()) {
                    echo $this->settings->replacePlaceholders($this->settings->getButtonCode());
                }
            }
        }

        public function initFooter() {
            if (!is_feed()) {
                try {
                    echo $this->settings->replacePlaceholders($this->settings->getButtonCode());
                } catch (Exception $e) {
                    $this->_log(sprintf('Unable to insert button in footer %s', $e->getMessage()));
                }
            }
        }

        public function addPrimaryConfigMenu() {
            $formHandler = new liveagent_Form_Handler($this->settings);
            add_menu_page(__('LiveAgent', LIVEAGENT_PLUGIN_NAME), __('LiveAgent', LIVEAGENT_PLUGIN_NAME), 'manage_options', liveagent_Form_Handler::TOP_LEVEL_OPTIONS_HANDLE, array(
                    $formHandler,
                    'printPrimaryPage'
            ), $this->getImgUrl() . 'menu-icon.png');
        }

        public function onNewUserRegistrationWoo($userId, $newCustomerData, $passwordGenerated) {
            $this->onNewUserRegistration($userId);
        }

        public function onNewUserRegistration($userId) {
            if (!$this->settings->isSignupIntegrationEnabled()) {
                $this->_log(__('Signup integration disabled - skipping new customer creation'));
                return;
            }
            $this->_log(__('Trying to create a new customer'));

            $apikey = $this->settings->getApiKey();
            if ($apikey === '') {
                $this->_log(__('API v1 key is missing! Registration of new user cancelled.'));
                return;
            }

            $user = new WP_User($userId);
            $name = $this->resolveCustomerName($user);

            $customerParams = array(
                'apikey' => $apikey,
                'email' => $user->user_email,
                'note' => 'Created by WP plugin'
            );

            if (!empty($name)) {
                $customerParams['name'] = $name;
            }

            try {
                $contactId = $this->settings->createCustomer($customerParams);
            } catch (liveagent_Exception_ConnectProblem $e) {
                $this->_log('Error creating customer: ' . $e->getMessage());
                return;
            }
        }

        private function resolveCustomerName(WP_User $user) {
            $first_name = $user->first_name;
            $last_name = $user->last_name;
            if ($first_name == '') {
                if(isset( $_POST['first_name'] ) && $_POST['first_name'] != '') {
                    $first_name = $_POST['first_name'];
                } elseif (isset( $_POST['billing_first_name'] ) && $_POST['billing_first_name'] != '') {
                    $first_name = $_POST['billing_first_name'];
                }
            }
            if ($last_name == '') {
                if(isset( $_POST['last_name'] ) && $_POST['last_name'] != '') {
                    $last_name = $_POST['last_name'];
                } elseif (isset( $_POST['billing_last_name'] ) && $_POST['billing_last_name'] != '') {
                    $last_name = $_POST['billing_last_name'];
                }
            }
            return $first_name.' '.$last_name;
        }
    }
}

$liveagent = liveagent::getInstance();
$liveagent->activate();