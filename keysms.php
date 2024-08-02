<?php

/*
 * Plugin Name: KeySMS
 * Plugin URI: https://github.com/ApilityLabs/keysms-wordpress
 * Description: KeySMS for Wordpress
 * Version: 1.0
 * Author: KeySMS
 * Author URI: https://app.keysms.no
 * text Domain: keysms
 * Domain Path: /languages
 * License: GPL-2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

use KeySMS\KeySMS;

if (!defined('ABSPATH')) {
    exit;
}

load_plugin_textdomain(
    'keysms',
    false,
    basename(__FILE__, '.php') . '/languages'
);

include_once __DIR__ . '/includes/keysms-settings.php';
include_once __DIR__ . '/includes/keysms-admin-page.php';
include_once __DIR__ . '/includes/keysms-woocomerce.php';
include_once __DIR__ . '/includes/KeySMS.php';

KeySMS::init();
