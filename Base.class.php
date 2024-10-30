<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

if (!class_exists('liveagent_Base')) {
    class liveagent_Base {
        const URL_SEPARATOR = '/';
        const IMG_PATH = 'resources/img/';
        const JS_PATH = 'resources/js/';
        const CSS_PATH = 'resources/css/';
        const REMOTE_SCRIPTS_DIR = 'scripts/';
        const TEMPLATES_PATH = 'templates/';

        protected function _log($message) {
            if (!$this->isPluginDebugMode()) {
                return;
            }
            if (is_array($message) || is_object($message)) {
                $message = var_export($message, true);
            }
            $message = LIVEAGENT_PLUGIN_NAME . ' plugin log: ' . $message;
            error_log($message);
            echo $message;
        }

        protected function isPluginDebugMode() { // defined in Config.php
            return defined('LIVEAGENT_DEBUG_MODE') && LIVEAGENT_DEBUG_MODE == true;
        }

        protected function getTemplatesPath() {
            return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . LIVEAGENT_PLUGIN_NAME . DIRECTORY_SEPARATOR . self::TEMPLATES_PATH;
        }

        protected function getImgUrl() {
            return plugins_url() . self::URL_SEPARATOR . LIVEAGENT_PLUGIN_NAME . self::URL_SEPARATOR . self::IMG_PATH;
        }

        protected function getJsUrl() {
            return plugins_url() . self::URL_SEPARATOR . LIVEAGENT_PLUGIN_NAME . self::URL_SEPARATOR . self::JS_PATH;
        }

        protected function getCssUrl() {
            return plugins_url() . self::URL_SEPARATOR . LIVEAGENT_PLUGIN_NAME . self::URL_SEPARATOR . self::CSS_PATH;
        }

        protected function getStylesheetHeaderLink($filename) {
            return '<link type="text/css" rel="stylesheet" href="' . $this->getCssUrl() . $filename . '?ver=' . LIVEAGENT_PLUGIN_VERSION . '" />' . "\n";
        }
    }
}