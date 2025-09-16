<?php
declare(strict_types=1);

/**
 * WooCommerce Settings Page
 */
class Ict_Mcp_Settings_Page extends WC_Settings_Page {
    
    public function __construct() {
        $this->id = 'ict_mcp_restricted_purchase';
        $this->label = esc_html__('Restricted Purchase', 'itc-woo-product-sell-restrict');
        
        parent::__construct();
    }
    
    public function get_settings(): array {
        return apply_filters('ict_mcp_restricted_purchase_settings', [
            [
                'title' => esc_html__('Restricted Purchase Settings', 'itc-woo-product-sell-restrict'),
                'type' => 'title',
                'desc' => esc_html__('Configure which products are restricted for purchase in specific countries.', 'itc-woo-product-sell-restrict'),
                'id' => 'ict_mcp_settings_section'
            ],
            [
                'title' => esc_html__('Restricted Countries', 'itc-woo-product-sell-restrict'),
                'desc' => esc_html__('Select countries where restricted products cannot be purchased.', 'itc-woo-product-sell-restrict'),
                'id' => 'ict_mcp_restricted_countries',
                'type' => 'multiselect',
                'class' => 'wc-enhanced-select',
                'css' => 'min-width: 300px;',
                'options' => $this->get_country_options(),
                'desc_tip' => true,
                'custom_attributes' => [
                    'data-placeholder' => esc_attr__('Select countries...', 'itc-woo-product-sell-restrict')
                ]
            ],
            [
                'title' => esc_html__('Restricted Products', 'itc-woo-product-sell-restrict'),
                'desc' => esc_html__('Select products that are restricted for purchase in the specified countries.', 'itc-woo-product-sell-restrict'),
                'id' => 'ict_mcp_restricted_products',
                'type' => 'multiselect',
                'class' => 'wc-product-search',
                'css' => 'min-width: 300px;',
                'options' => $this->get_product_options(),
                'desc_tip' => true,
                'custom_attributes' => [
                    'data-placeholder' => esc_attr__('Search for products...', 'itc-woo-product-sell-restrict'),
                    'data-action' => 'woocommerce_json_search_products_and_variations',
                    'data-multiple' => 'true',
                    'data-exclude_type' => 'grouped'
                ]
            ],
            [
                'title' => esc_html__('Restriction Message', 'itc-woo-product-sell-restrict'),
                'desc' => esc_html__('Customize the message shown to customers when restricted products are in their cart.', 'itc-woo-product-sell-restrict'),
                'id' => 'ict_mcp_restriction_message',
                'type' => 'textarea',
                'css' => 'width: 100%; height: 80px;',
                'default' => esc_html__('These products are not available for purchase in your country.', 'itc-woo-product-sell-restrict'),
                'desc_tip' => true
            ],
            [
                'type' => 'sectionend',
                'id' => 'ict_mcp_settings_section'
            ]
        ]);
    }
    
    private function get_country_options(): array {
        $countries = WC()->countries->get_countries();
        $options = [];
        
        foreach ($countries as $code => $name) {
            $options[$code] = $name;
        }
        
        return $options;
    }
    
    private function get_product_options(): array {
        $settings = Ict_Mcp_Settings::get_settings();
        $products = [];
        
        if (!empty($settings['restricted_products'])) {
            foreach ($settings['restricted_products'] as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $products[$product_id] = $product->get_formatted_name();
                }
            }
        }
        
        return $products;
    }
    
    public function output(): void {
        global $current_section;
        
        $settings = $this->get_settings();
        WC_Admin_Settings::output_fields($settings);
    }
    
    public function save(): void {
        global $current_section;
        
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
        
        // Save custom fields
        $restricted_countries = isset($_POST['ict_mcp_restricted_countries']) ? 
            array_map('sanitize_text_field', $_POST['ict_mcp_restricted_countries']) : [];
        
        $restricted_products = isset($_POST['ict_mcp_restricted_products']) ? 
            array_map('absint', $_POST['ict_mcp_restricted_products']) : [];
        
        $restriction_message = isset($_POST['ict_mcp_restriction_message']) ? 
            sanitize_textarea_field($_POST['ict_mcp_restriction_message']) : '';
        
        update_option('ict_mcp_settings', [
            'restricted_countries' => $restricted_countries,
            'restricted_products' => $restricted_products,
            'restriction_message' => $restriction_message
        ]);
        
        // Clear any caches
        wp_cache_delete('ict_mcp_settings', 'options');
    }
}
