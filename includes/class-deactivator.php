<?php
declare(strict_types=1);

/**
 * Plugin deactivation class
 */
class Ict_Mcp_Deactivator {
    
    public static function deactivate(): void {
        // Clear any scheduled events
        wp_clear_scheduled_hook('ict_mcp_cleanup');
        
        // Clear transients
        delete_transient('ict_mcp_activated');
        delete_transient('ict_mcp_settings_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
