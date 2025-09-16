<?php
declare(strict_types=1);

/**
 * Plugin Name: ITC WooCommerce Product Sell Restrict
 * Plugin URI: https://example.com/itc-woo-product-sell-restrict
 * Description: Restrict WooCommerce product sales based on customer billing/shipping country addresses.
 * Version: 1.0.0
 * Author: ITC Md Mamunuzzaman
 * Author URI: https://example.com
 * Text Domain: itc-woo-product-sell-restrict
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ICT_MCP_PLUGIN_FILE', __FILE__);
define('ICT_MCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ICT_MCP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ICT_MCP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ICT_MCP_VERSION', '1.0.0');

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . 
             esc_html__('ITC WooCommerce Product Sell Restrict requires WooCommerce to be installed and active.', 'itc-woo-product-sell-restrict') . 
             '</p></div>';
    });
    return;
}

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'Ict_Mcp_') !== 0) {
        return;
    }
    
    $class_file = strtolower(str_replace(['Ict_Mcp_', '_'], ['', '-'], $class));
    $file_path = ICT_MCP_PLUGIN_DIR . 'includes/class-' . $class_file . '.php';
    
    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

// Initialize the plugin
function ict_mcp_init() {
    load_plugin_textdomain('itc-woo-product-sell-restrict', false, dirname(ICT_MCP_PLUGIN_BASENAME) . '/languages');
    
    // Initialize main plugin class
    Ict_Mcp_Main::get_instance();
}
add_action('plugins_loaded', 'ict_mcp_init');

// Activation hook
register_activation_hook(__FILE__, ['Ict_Mcp_Activator', 'activate']);

// Deactivation hook
register_deactivation_hook(__FILE__, ['Ict_Mcp_Deactivator', 'deactivate']);

// Uninstall hook
register_uninstall_hook(__FILE__, ['Ict_Mcp_Uninstaller', 'uninstall']);
