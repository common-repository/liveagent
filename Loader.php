<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Martin Pullmann
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

include_once(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . LIVEAGENT_PLUGIN_NAME . '/LoadClassException.class.php');

if (!class_exists('liveagent_Loader')) {
    class liveagent_Loader {

        /**
         * @var liveagent_Loader
         */
        private static $instance = null;

        /**
         * @return liveagent_Loader
         */
        public static function getInstance() {
            if (self::$instance == null) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        /**
         * @throws liveagent_LoadClassException
         */
        public function load() {
            $this->loadBaseClasses();
            $this->loadForms();
            $this->loadHelpers();
        }

        /**
         * @throws liveagent_LoadClassException
         */
        private function loadHelpers() {
            $this->loadClass('liveagent_Helper_Connect');
        }

        /**
         * @throws liveagent_LoadClassException
         */
        private function loadForms() {
            $this->loadClass('liveagent_Form_Base');
            $this->loadClass('liveagent_Form_Settings_Account');
            $this->loadClass('liveagent_Form_Settings_ButtonCode');
            $this->loadClass('liveagent_Form_Signup');
            $this->loadClass('liveagent_Form_Validator_Base');
            $this->loadClass('liveagent_Form_Validator_Account');
        }

        /**
         * @throws liveagent_LoadClassException
         */
        private function loadBaseClasses() {
            $this->loadClass('liveagent_Base');
            $this->loadClass('liveagent_Settings');
            $this->loadClass('liveagent_Exception_SettingNotValid');
            $this->loadClass('liveagent_Exception_ConnectProblem');
            $this->loadClass('liveagent_Exception_SignupFail');
            $this->loadClass('liveagent_Form_Handler');
        }

        /**
         * @param $className
         * @throws liveagent_LoadClassException
         */
        protected function loadClass($className) {
            if (!class_exists($className, false)) {
                $path = str_replace('_', DIRECTORY_SEPARATOR, $className);
                $path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $path . '.class.php';
                $this->requireClass($path);
            }
        }

        /**
         * @param $pathToFile
         * @throws liveagent_LoadClassException
         */
        protected function requireClass($pathToFile) {
            if (!file_exists($pathToFile)) {
                throw new liveagent_LoadClassException('File ' . $pathToFile . ' does NOT exist! Can not continue.');
            }
            require_once $pathToFile;
        }
    }
}