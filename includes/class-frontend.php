<?php
declare(strict_types=1);

/**
 * Frontend functionality class
 */
class Ict_Mcp_Frontend {
    
    private static $restriction_notice_added = false;
    
    public function __construct() {
        add_action('wp_loaded', [$this, 'reset_notice_flag']);
        add_action('wp_loaded', [$this, 'check_cart_restrictions']);
        add_action('woocommerce_checkout_process', [$this, 'validate_checkout']);
        add_action('woocommerce_checkout_order_processed', [$this, 'validate_order_processed']);
        add_action('woocommerce_before_checkout_process', [$this, 'validate_before_checkout']);
        add_action('wp_ajax_ict_mcp_remove_restricted_products', [$this, 'ajax_remove_restricted_products']);
        add_action('wp_ajax_nopriv_ict_mcp_remove_restricted_products', [$this, 'ajax_remove_restricted_products']);
        add_action('woocommerce_cart_updated', [$this, 'on_cart_updated']);
        add_action('woocommerce_checkout_update_order_review', [$this, 'validate_order_review']);
        add_filter('woocommerce_checkout_process', [$this, 'prevent_checkout_if_restricted'], 10, 0);
        add_action('woocommerce_before_single_product', [$this, 'add_product_restriction_notice'], 5);
        add_action('wp_head', [$this, 'add_conditional_css']);
    }
    
    public function reset_notice_flag(): void {
        self::$restriction_notice_added = false;
    }
    
    public function check_cart_restrictions(): void {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        
        $restricted_items = $this->get_restricted_items_in_cart();
        if (empty($restricted_items)) {
            return;
        }
        
        // Add notice to inform user about restrictions
        $this->add_restriction_notice($restricted_items);
    }
    
    public function validate_checkout(): void {
        $this->perform_restriction_validation();
    }
    
    public function validate_order_processed(): void {
        $this->perform_restriction_validation();
    }
    
    public function validate_before_checkout(): void {
        $this->perform_restriction_validation();
    }
    
    public function validate_order_review(): void {
        $this->perform_restriction_validation();
    }
    
    private function perform_restriction_validation(): void {
        $restricted_items = $this->get_restricted_items_in_cart();
        if (empty($restricted_items)) {
            return;
        }
        
        // Prevent duplicate notices
        if (self::$restriction_notice_added) {
            return;
        }
        
        $message = Ict_Mcp_Settings::get_restriction_message();
        $customer_country = $this->get_customer_country();
        
        // Add debug logging
        error_log('ICT MCP Validation - Customer Country: ' . $customer_country);
        error_log('ICT MCP Validation - Restricted Items: ' . print_r($restricted_items, true));
        
        // Create a single notice with all restricted products
        $product_names = wp_list_pluck($restricted_items, 'product_name');
        $products_list = '<ul><li>' . implode('</li><li>', array_map('esc_html', $product_names)) . '</li></ul>';
        
        // Add single consolidated notice with proper HTML preservation
        $notice_html = sprintf(
            '<div class="ict-mcp-checkout-restriction-notice">
                <p><strong>%s</strong></p>
                <div class="ict-mcp-restriction-message">%s</div>
                <div class="ict-mcp-restricted-products-list">%s</div>
            </div>',
            esc_html__('Purchase Restriction Notice', 'itc-woo-product-sell-restrict'),
            wp_kses_post($message),
            $products_list
        );
        
        // Add notice using WooCommerce's notice system but preserve HTML
        wc_add_notice($notice_html, 'error');
        
        // Prevent checkout by adding a critical error
        wc_add_notice(
            esc_html__('Checkout cannot be completed due to restricted products in your cart.', 'itc-woo-product-sell-restrict'),
            'error'
        );
        
        // Mark notice as added to prevent duplicates
        self::$restriction_notice_added = true;
    }
    
    public function prevent_checkout_if_restricted(): void {
        $restricted_items = $this->get_restricted_items_in_cart();
        if (!empty($restricted_items)) {
            // Throw an exception to stop checkout process
            throw new Exception(esc_html__('Checkout cannot be completed due to restricted products.', 'itc-woo-product-sell-restrict'));
        }
    }
    
    public function ajax_remove_restricted_products(): void {
        check_ajax_referer('ict_mcp_frontend_nonce', 'nonce');
        
        if (!isset($_POST['product_ids']) || !is_array($_POST['product_ids'])) {
            wp_send_json_error(esc_html__('Invalid product IDs.', 'itc-woo-product-sell-restrict'));
        }
        
        $product_ids = array_map('absint', $_POST['product_ids']);
        $removed_items = [];
        
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (in_array($cart_item['product_id'], $product_ids) || in_array($cart_item['variation_id'], $product_ids)) {
                $removed_items[] = $cart_item['data']->get_name();
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
        
        WC()->cart->calculate_totals();
        
        wp_send_json_success([
            'message' => sprintf(
                esc_html__('Removed restricted items: %s', 'itc-woo-product-sell-restrict'),
                implode(', ', $removed_items)
            ),
            'removed_items' => $removed_items
        ]);
    }
    
    public function on_cart_updated(): void {
        // Check if this is an AJAX request for cart update
        if (wp_doing_ajax() && isset($_POST['action']) && $_POST['action'] === 'woocommerce_update_shipping_method') {
            $this->check_cart_restrictions();
        }
    }
    
    private function get_restricted_items_in_cart(): array {
        $customer_country = $this->get_customer_country();
        $restricted_countries = Ict_Mcp_Settings::get_restricted_countries();
        $restricted_products = Ict_Mcp_Settings::get_restricted_products();
        
        // Debug logging
        error_log('ICT MCP Cart Check - Customer Country: ' . ($customer_country ?: 'null'));
        error_log('ICT MCP Cart Check - Restricted Countries: ' . print_r($restricted_countries, true));
        error_log('ICT MCP Cart Check - Restricted Products: ' . print_r($restricted_products, true));
        
        if (!$customer_country) {
            error_log('ICT MCP Cart Check - No customer country found');
            return [];
        }
        
        if (empty($restricted_countries) || !in_array($customer_country, $restricted_countries)) {
            error_log('ICT MCP Cart Check - Customer country not in restricted list');
            return [];
        }
        
        if (empty($restricted_products)) {
            error_log('ICT MCP Cart Check - No restricted products configured');
            return [];
        }
        
        $restricted_items = [];
        
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
            $parent_product_id = $cart_item['product_id'];
            
            // Check both variation ID and parent product ID
            if (in_array($product_id, $restricted_products) || in_array($parent_product_id, $restricted_products)) {
                $restricted_items[] = [
                    'cart_item_key' => $cart_item_key,
                    'product_id' => $product_id,
                    'parent_product_id' => $parent_product_id,
                    'product_name' => $cart_item['data']->get_name(),
                    'quantity' => $cart_item['quantity']
                ];
                
                error_log('ICT MCP Cart Check - Found restricted item: ' . $cart_item['data']->get_name());
            }
        }
        
        error_log('ICT MCP Cart Check - Total restricted items: ' . count($restricted_items));
        return $restricted_items;
    }
    
    private function get_customer_country(): ?string {
        // Check if we're on checkout page and form data is available
        if (is_checkout() && !empty($_POST)) {
            // Check posted billing country first
            if (!empty($_POST['billing_country'])) {
                return sanitize_text_field($_POST['billing_country']);
            }
            // Check posted shipping country
            if (!empty($_POST['shipping_country'])) {
                return sanitize_text_field($_POST['shipping_country']);
            }
        }
        
        // Check billing address from customer object
        $billing_country = WC()->customer->get_billing_country();
        if ($billing_country && $billing_country !== '') {
            return $billing_country;
        }
        
        // Check shipping address from customer object
        $shipping_country = WC()->customer->get_shipping_country();
        if ($shipping_country && $shipping_country !== '') {
            return $shipping_country;
        }
        
        // Check if customer is logged in and has saved addresses
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $billing_country = get_user_meta($user_id, 'billing_country', true);
            if ($billing_country && $billing_country !== '') {
                return $billing_country;
            }
            
            $shipping_country = get_user_meta($user_id, 'shipping_country', true);
            if ($shipping_country && $shipping_country !== '') {
                return $shipping_country;
            }
        }
        
        // Check session data
        if (WC()->session) {
            $billing_country = WC()->session->get('customer_billing_country');
            if ($billing_country && $billing_country !== '') {
                return $billing_country;
            }
            
            $shipping_country = WC()->session->get('customer_shipping_country');
            if ($shipping_country && $shipping_country !== '') {
                return $shipping_country;
            }
        }
        
        return null;
    }
    
    private function add_restriction_notice(array $restricted_items): void {
        $message = Ict_Mcp_Settings::get_restriction_message();
        $product_names = wp_list_pluck($restricted_items, 'product_name');
        
        $notice = sprintf(
            '<div class="ict-mcp-restriction-notice" data-restricted-products="%s">
                <div class="ict-mcp-notice-header">
                    <div class="ict-mcp-notice-icon">⚠️</div>
                    <h4 class="ict-mcp-notice-title">%s</h4>
                </div>
                <div class="ict-mcp-notice-content">
                    <div class="ict-mcp-notice-message">%s</div>
                    <div class="ict-mcp-restricted-products">
                        <span class="ict-mcp-products-label">%s</span>
                        <ul class="ict-mcp-products-list">%s</ul>
                    </div>
                </div>
            </div>',
            esc_attr(wp_json_encode(wp_list_pluck($restricted_items, 'product_id'))),
            esc_html__('Purchase Restriction Notice', 'itc-woo-product-sell-restrict'),
            wp_kses_post($message),
            esc_html__('Restricted Products:', 'itc-woo-product-sell-restrict'),
            '<li>' . implode('</li><li>', array_map('esc_html', $product_names)) . '</li>'
        );
        
        // Add custom notice to cart page with HTML preservation
        if (is_cart()) {
            wc_add_notice($notice, 'notice');
        }
        
        // Store restriction data for JavaScript
        wp_localize_script('ict-mcp-frontend', 'ict_mcp_restrictions', [
            'restricted_items' => $restricted_items,
            'message' => $message
        ]);
    }
    
    public function add_product_restriction_notice(): void {
        global $product;
        
        if (!is_product()) {
            return;
        }
        
        // Ensure we have a valid WooCommerce product object
        if (!$product || !is_object($product) || !method_exists($product, 'get_id')) {
            $product = wc_get_product(get_the_ID());
        }
        
        if (!$product || !is_object($product) || !method_exists($product, 'get_id')) {
            return;
        }
        
        $restricted_products = Ict_Mcp_Settings::get_restricted_products();
        $restricted_countries = Ict_Mcp_Settings::get_restricted_countries();
        
        // Check if this product or its variations are restricted
        $product_id = $product->get_id();
        $is_restricted = false;
        $restricted_variations = [];
        
        if (in_array($product_id, $restricted_products)) {
            $is_restricted = true;
        } else {
            // Check variations for variable products
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    if (in_array($variation_id, $restricted_products)) {
                        $is_restricted = true;
                        $restricted_variations[] = $variation_id;
                    }
                }
            }
        }
        
        // Only show notice if there are restricted countries and this product is restricted
        if ($is_restricted && !empty($restricted_countries)) {
            $message = Ict_Mcp_Settings::get_restriction_message();
            $countries_list = implode(', ', array_map(function($country_code) {
                $countries = WC()->countries->get_countries();
                return $countries[$country_code] ?? $country_code;
            }, $restricted_countries));
            
            $notice_html = sprintf(
                '<div class="ict-mcp-product-restriction-notice woocommerce-notice">
                    <div class="ict-mcp-notice-header">
                        <div class="ict-mcp-notice-icon">⚠️</div>
                        <h4 class="ict-mcp-notice-title">%s</h4>
                    </div>
                    <div class="ict-mcp-notice-content">
                        <div class="ict-mcp-notice-message">%s</div>
                        <div class="ict-mcp-notice-countries">
                            <span class="ict-mcp-countries-label">%s</span>
                            <span class="ict-mcp-countries-list">%s</span>
                        </div>
                    </div>
                </div>',
                esc_html__('Purchase Restriction Notice', 'itc-woo-product-sell-restrict'),
                wp_kses_post($message),
                esc_html__('Restricted in:', 'itc-woo-product-sell-restrict'),
                esc_html($countries_list)
            );
            
            echo $notice_html;
        }
    }
    
    public function add_conditional_css(): void {
        // Only add CSS when restriction notices are actually shown
        $should_add_css = false;
        
        // Check if we're on a page that might show restriction notices
        if (is_cart() || is_checkout()) {
            $should_add_css = true;
        } elseif (is_product()) {
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
                    $should_add_css = true;
                }
            }
        }
        
        // Add minimal CSS only when needed
        if ($should_add_css) {
            echo '<style id="ict-mcp-conditional-css">
                /* ITC MCP Conditional Styles - Only loaded when restriction notices are shown */
                .ict-mcp-product-restriction-notice {
                    background: linear-gradient(135deg, #fff3cd 0%, #fef7e0 100%);
                    border: 2px solid #ffc107;
                    border-left: 6px solid #ff9800;
                    border-radius: 12px;
                    padding: 0;
                    margin: 20px 0 25px 0;
                    color: #856404;
                    position: relative;
                    box-shadow: 0 4px 20px rgba(255, 193, 7, 0.2);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    animation: ict-mcp-slideInUp 0.4s ease-out;
                    overflow: hidden;
                    z-index: 10;
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-notice-header {
                    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                    padding: 15px 20px;
                    display: flex;
                    align-items: center;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-notice-icon {
                    font-size: 24px;
                    margin-right: 12px;
                    animation: ict-mcp-pulse 2s infinite;
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-notice-title {
                    color: #fff;
                    font-weight: 700;
                    font-size: 16px;
                    margin: 0;
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-notice-content {
                    padding: 20px;
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-notice-message {
                    margin: 0 0 15px 0;
                    font-size: 14px;
                    line-height: 1.5;
                    color: #856404;
                    font-weight: 500;
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-notice-countries {
                    background: rgba(255, 255, 255, 0.7);
                    border-radius: 8px;
                    padding: 12px 15px;
                    border-left: 3px solid #ffc107;
                    display: flex;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-countries-label {
                    font-weight: 600;
                    color: #d63031;
                    font-size: 13px;
                }
                
                .ict-mcp-product-restriction-notice .ict-mcp-countries-list {
                    color: #495057;
                    font-size: 13px;
                    font-weight: 500;
                }
                
                @keyframes ict-mcp-slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                @keyframes ict-mcp-pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                
                @media (max-width: 768px) {
                    .ict-mcp-product-restriction-notice {
                        margin: 10px 0;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-notice-header {
                        padding: 12px 15px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-notice-icon {
                        font-size: 20px;
                        margin-right: 10px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-notice-title {
                        font-size: 14px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-notice-content {
                        padding: 15px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-notice-message {
                        font-size: 13px;
                        margin-bottom: 12px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-notice-countries {
                        padding: 10px 12px;
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 5px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-countries-label {
                        font-size: 12px;
                    }
                    
                    .ict-mcp-product-restriction-notice .ict-mcp-countries-list {
                        font-size: 12px;
                    }
                }
            </style>';
        }
    }
}
