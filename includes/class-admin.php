<?php
declare(strict_types=1);

/**
 * Admin functionality class
 */
class Ict_Mcp_Admin {
    
    public function __construct() {
        add_action('admin_init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_ict_mcp_search_products', [$this, 'ajax_search_products']);
    }
    
    public function init(): void {
        // Add custom fields to product edit page
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_product_restriction_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_restriction_field']);
    }
    
    public function enqueue_admin_scripts(): void {
        // Only load on WooCommerce settings pages
        if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && 
            isset($_GET['tab']) && $_GET['tab'] === 'ict_mcp_restricted_purchase') {
            
            wp_enqueue_style(
                'ict-mcp-admin',
                ICT_MCP_PLUGIN_URL . 'assets/css/ict-mcp-admin.css',
                [],
                ICT_MCP_VERSION
            );
            
            wp_enqueue_script(
                'ict-mcp-admin',
                ICT_MCP_PLUGIN_URL . 'assets/js/ict-mcp-admin.js',
                ['jquery'],
                ICT_MCP_VERSION,
                true
            );
        }
    }
    
    public function add_product_restriction_field(): void {
        global $post;
        
        $is_restricted = get_post_meta($post->ID, '_ict_mcp_is_restricted', true);
        
        woocommerce_wp_checkbox([
            'id' => '_ict_mcp_is_restricted',
            'label' => esc_html__('Restricted Product', 'itc-woo-product-sell-restrict'),
            'description' => esc_html__('Check this box to mark this product as restricted for certain countries.', 'itc-woo-product-sell-restrict'),
            'value' => $is_restricted ? 'yes' : 'no'
        ]);
    }
    
    public function save_product_restriction_field(int $post_id): void {
        $is_restricted = isset($_POST['_ict_mcp_is_restricted']) ? 'yes' : 'no';
        update_post_meta($post_id, '_ict_mcp_is_restricted', $is_restricted);
    }
    
    public function ajax_search_products(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(esc_html__('Insufficient permissions.', 'itc-woo-product-sell-restrict'));
        }
        
        // Check both GET and POST for the search term
        $term = '';
        if (isset($_POST['q']) && !empty($_POST['q'])) {
            $term = sanitize_text_field($_POST['q']);
        } elseif (isset($_GET['q']) && !empty($_GET['q'])) {
            $term = sanitize_text_field($_GET['q']);
        } elseif (isset($_POST['term']) && !empty($_POST['term'])) {
            $term = sanitize_text_field($_POST['term']);
        }
        
        // Debug logging
        error_log('ICT MCP Search Debug - Term: ' . $term);
        error_log('ICT MCP Search Debug - POST data: ' . print_r($_POST, true));
        
        if (empty($term)) {
            wp_send_json_success([]);
        }
        
        // Use WP_Query for better product search
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $term
        ];
        
        $query = new WP_Query($args);
        $results = [];
        
        error_log('ICT MCP Search Debug - Query found posts: ' . $query->found_posts);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $results[] = [
                        'id' => $product->get_id(),
                        'text' => $product->get_formatted_name()
                    ];
                }
            }
        }
        
        wp_reset_postdata();
        error_log('ICT MCP Search Debug - Results count: ' . count($results));
        wp_send_json_success($results);
    }
}
