<?php

/**
 * Plugin Name: Tickets Expiration for Ticket Manager
 * Description: Validity date for the ticket handler.
 * Version: 0.0.2
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager-expiration-date
 * Requires Plugins: dl-ticket-manager
 */

use DL\TicketsExpirationDate\Plugin;

defined('ABSPATH') || exit;

require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager-expiration-date', false, dirname(plugin_basename(__FILE__)) . '/languages');

    (new Plugin())->init();
});
