<?php
declare(strict_types=1);

/**
 * Translation support class for WPML/Polylang
 */
class Ict_Mcp_Translations {
    
    public function __construct() {
        add_action('init', [$this, 'init_translations']);
        add_filter('wpml_config_array', [$this, 'add_wpml_config']);
        add_action('pll_init', [$this, 'init_polylang_support']);
    }
    
    public function init_translations(): void {
        load_plugin_textdomain(
            'itc-woo-product-sell-restrict',
            false,
            dirname(ICT_MCP_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    public function add_wpml_config(array $config): array {
        $config['wpml-config']['admin-texts']['key'] = [
            [
                'name' => 'ict_mcp_settings',
                'type' => 'option',
                'children' => [
                    [
                        'name' => 'restriction_message',
                        'type' => 'option'
                    ]
                ]
            ]
        ];
        
        return $config;
    }
    
    public function init_polylang_support(): void {
        if (function_exists('pll_register_string')) {
            pll_register_string(
                'ict_mcp_restriction_message',
                get_option('ict_mcp_restriction_message', ''),
                'itc-woo-product-sell-restrict'
            );
        }
    }
    
    public static function get_translated_message(): string {
        $message = Ict_Mcp_Settings::get_restriction_message();
        
        // WPML support
        if (function_exists('icl_t')) {
            $message = icl_t('itc-woo-product-sell-restrict', 'ict_mcp_restriction_message', $message);
        }
        
        // Polylang support
        if (function_exists('pll__')) {
            $message = pll__($message);
        }
        
        return $message;
    }
}
