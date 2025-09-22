<?php
declare(strict_types=1);

/**
 * Settings management class
 */
class Ict_Mcp_Settings {
    
    private $option_group = 'ict_mcp_settings';
    private $option_name = 'ict_mcp_settings';
    
    public function __construct() {
        add_action('admin_init', [$this, 'init_settings']);
        add_filter('woocommerce_get_settings_pages', [$this, 'add_settings_page']);
    }
    
    public function init_settings(): void {
        register_setting(
            $this->option_group,
            $this->option_name,
            [$this, 'sanitize_settings']
        );
    }
    
    public function add_settings_page(array $settings): array {
        $settings[] = new Ict_Mcp_Settings_Page();
        return $settings;
    }
    
    public function sanitize_settings(array $input): array {
        $sanitized = [];
        
        if (isset($input['restricted_countries'])) {
            $sanitized['restricted_countries'] = array_map('sanitize_text_field', $input['restricted_countries']);
        }
        
        if (isset($input['restricted_products'])) {
            $sanitized['restricted_products'] = array_map('absint', $input['restricted_products']);
        }
        
        if (isset($input['restriction_message'])) {
            $sanitized['restriction_message'] = wp_kses_post($input['restriction_message']);
        }
        
        return $sanitized;
    }
    
    public static function get_settings(): array {
        $defaults = [
            'restricted_countries' => [],
            'restricted_products' => [],
            'restriction_message' => esc_html__('These products are not available for purchase in your country.', 'itc-woo-product-sell-restrict')
        ];
        
        $settings = get_option('ict_mcp_settings', $defaults);
        return wp_parse_args($settings, $defaults);
    }
    
    public static function get_restricted_countries(): array {
        $settings = self::get_settings();
        return $settings['restricted_countries'];
    }
    
    public static function get_restricted_products(): array {
        $settings = self::get_settings();
        return $settings['restricted_products'];
    }
    
    public static function get_restriction_message(): string {
        $settings = self::get_settings();
        return $settings['restriction_message'];
    }
}
