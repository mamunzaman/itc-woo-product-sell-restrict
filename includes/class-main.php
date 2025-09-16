<?php
declare(strict_types=1);

/**
 * Main plugin class
 */
class Ict_Mcp_Main {
    
    private static $instance = null;
    
    private function __construct() {
        $this->init_hooks();
    }
    
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function init_hooks(): void {
        add_action('init', [$this, 'init']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    public function init(): void {
        // Initialize translations
        new Ict_Mcp_Translations();
        
        // Initialize settings
        new Ict_Mcp_Settings();
        
        // Initialize frontend functionality
        new Ict_Mcp_Frontend();
        
        // Initialize admin functionality
        if (is_admin()) {
            new Ict_Mcp_Admin();
        }
    }
    
    public function admin_init(): void {
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . ICT_MCP_PLUGIN_BASENAME, [$this, 'add_settings_link']);
    }
    
    public function add_settings_link(array $links): array {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=ict_mcp_restricted_purchase') . '">' . 
                        esc_html__('Settings', 'itc-woo-product-sell-restrict') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    public function enqueue_frontend_scripts(): void {
        $should_load = false;
        
        // Load on cart/checkout pages
        if (is_cart() || is_checkout()) {
            $should_load = true;
        }
        
        // Load on single product pages if product is restricted
        if (is_product()) {
            global $product;
            
            // Ensure we have a valid WooCommerce product object
            if (!$product || !is_object($product) || !method_exists($product, 'get_id')) {
                $product = wc_get_product(get_the_ID());
            }
            
            if ($product && is_object($product) && method_exists($product, 'get_id')) {
                $restricted_products = Ict_Mcp_Settings::get_restricted_products();
                $product_id = $product->get_id();
                $is_restricted = false;
                
                if (in_array($product_id, $restricted_products)) {
                    $is_restricted = true;
                } else {
                    // Check variations for variable products
                    if (method_exists($product, 'is_type') && $product->is_type('variable')) {
                        $variations = $product->get_children();
                        foreach ($variations as $variation_id) {
                            if (in_array($variation_id, $restricted_products)) {
                                $is_restricted = true;
                                break;
                            }
                        }
                    }
                }
                
                if ($is_restricted && !empty(Ict_Mcp_Settings::get_restricted_countries())) {
                    $should_load = true;
                }
            }
        }
        
        // Only enqueue if needed
        if ($should_load) {
            wp_enqueue_script(
                'ict-mcp-frontend',
                ICT_MCP_PLUGIN_URL . 'assets/js/ict-mcp-frontend.js',
                ['jquery', 'wc-checkout'],
                ICT_MCP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ict-mcp-frontend',
                ICT_MCP_PLUGIN_URL . 'assets/css/ict-mcp-frontend.css',
                [],
                ICT_MCP_VERSION
            );
            
            wp_localize_script('ict-mcp-frontend', 'ict_mcp_frontend', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ict_mcp_frontend_nonce'),
                'messages' => [
                    'confirm_removal' => get_option('ict_mcp_restriction_message', 
                        esc_html__('These products are not available for purchase in your country.', 'itc-woo-product-sell-restrict'))
                ]
            ]);
        }
    }
    
    public function enqueue_admin_scripts(): void {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'woocommerce_page_wc-settings') {
            wp_enqueue_script(
                'ict-mcp-admin',
                ICT_MCP_PLUGIN_URL . 'assets/js/ict-mcp-admin.js',
                ['jquery', 'select2'],
                ICT_MCP_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ict-mcp-admin',
                ICT_MCP_PLUGIN_URL . 'assets/css/ict-mcp-admin.css',
                [],
                ICT_MCP_VERSION
            );
        }
    }
}
