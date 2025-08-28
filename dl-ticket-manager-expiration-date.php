<?php

/**
 * Plugin Name: Tickets Expiration for Ticket Manager
 * Description: Fecha de validez para el gestor de tickets.
 * Version: 0.0.2
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager-expiration-date
 * Requires Plugins: dl-ticket-manager
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Plugin.php';

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager-expiration-date', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $plugin = new TMExpirationDatePlugin();
    $plugin->init();
});
