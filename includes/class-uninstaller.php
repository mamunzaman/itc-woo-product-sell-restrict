<?php
declare(strict_types=1);

/**
 * Plugin uninstall class
 */
class Ict_Mcp_Uninstaller {
    
    public static function uninstall(): void {
        // Check if user has permission
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Check if this is a multisite installation
        if (is_multisite()) {
            $sites = get_sites();
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                self::cleanup_site_data();
                restore_current_blog();
            }
        } else {
            self::cleanup_site_data();
        }
    }
    
    private static function cleanup_site_data(): void {
        // Remove plugin options
        delete_option('ict_mcp_settings');
        delete_option('ict_mcp_version');
        
        // Remove user meta (if any)
        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            delete_user_meta($user_id, 'ict_mcp_user_preferences');
        }
        
        // Remove product meta
        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        foreach ($products as $product_id) {
            delete_post_meta($product_id, '_ict_mcp_is_restricted');
        }
        
        // Clear all transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ict_mcp_%'"
        );
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ict_mcp_%'"
        );
        
        // Clear any cached data
        wp_cache_flush();
    }
}
