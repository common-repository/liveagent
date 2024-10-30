<?php
/**
 *   @copyright Copyright (c) 2015 Quality Unit s.r.o.
 *   @author Juraj Simon
 *   @package WpLiveAgentPlugin
 *   @version 1.0.0
 *
 *   Licensed under GPL2
 */

/**
 * enable or disable plugin debug mode
 * if enabled, every error will be logged with standard wordpress logger and also printed to the screen
 * default value: false
 */
if (!defined('LIVEAGENT_DEBUG_MODE')) {
    define('LIVEAGENT_DEBUG_MODE', false);
}
if (!defined('LIVEAGENT_TEST_MODE')) {
	define('LIVEAGENT_TEST_MODE', false);
}
