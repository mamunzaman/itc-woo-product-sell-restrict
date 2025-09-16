<?php
declare(strict_types=1);

/**
 * Plugin activation class
 */
class Ict_Mcp_Activator {
    
    public static function activate(): void {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(ICT_MCP_PLUGIN_BASENAME);
            wp_die(
                esc_html__('This plugin requires WordPress 5.0 or higher.', 'itc-woo-product-sell-restrict'),
                esc_html__('Plugin Activation Error', 'itc-woo-product-sell-restrict'),
                ['back_link' => true]
            );
        }
        
        // Check WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(ICT_MCP_PLUGIN_BASENAME);
            wp_die(
                esc_html__('This plugin requires WooCommerce to be installed and active.', 'itc-woo-product-sell-restrict'),
                esc_html__('Plugin Activation Error', 'itc-woo-product-sell-restrict'),
                ['back_link' => true]
            );
        }
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        set_transient('ict_mcp_activated', true, 30);
    }
    
    private static function set_default_options(): void {
        $default_settings = [
            'restricted_countries' => [],
            'restricted_products' => [],
            'restriction_message' => esc_html__('These products are not available for purchase in your country.', 'itc-woo-product-sell-restrict')
        ];
        
        if (!get_option('ict_mcp_settings')) {
            add_option('ict_mcp_settings', $default_settings);
        }
        
        // Set version
        update_option('ict_mcp_version', ICT_MCP_VERSION);
    }
}
