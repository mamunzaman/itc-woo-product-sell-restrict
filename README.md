# ITC WooCommerce Product Sell Restrict

A WordPress plugin that restricts WooCommerce product sales based on customer billing/shipping country addresses.

## Description

This plugin allows you to restrict certain products from being purchased by customers in specific countries. It detects customer addresses during checkout and prevents restricted products from being purchased, with options to automatically remove them or prevent checkout completion.

## Features

- **Country-based Restrictions**: Select multiple countries where products cannot be purchased
- **Product Selection**: Choose specific products to restrict using WooCommerce's product search
- **Address Detection**: Automatically detects customer billing and shipping addresses
- **User-friendly Interface**: Shows clear messages to customers about restrictions
- **Confirmation Dialog**: Allows customers to remove restricted products or prevent checkout
- **Customizable Messages**: Customize the restriction message text
- **Translation Ready**: Full support for WPML and Polylang
- **Responsive Design**: Works on all device sizes
- **WooCommerce Integration**: Seamlessly integrates with WooCommerce settings

## Installation

1. Upload the plugin files to `/wp-content/plugins/itc-woo-product-sell-restrict/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WooCommerce → Settings → Restricted Purchase to configure

## Configuration

1. Navigate to **WooCommerce → Settings → Restricted Purchase**
2. Select the countries where products should be restricted
3. Choose the products to restrict using the product search
4. Customize the message shown to customers
5. Save your settings

## How It Works

1. **Detection**: The plugin monitors customer billing and shipping addresses during checkout
2. **Validation**: When a customer from a restricted country has restricted products in their cart, the plugin shows a warning
3. **Action**: Customers can choose to remove restricted products or be prevented from completing checkout
4. **Enforcement**: The plugin prevents checkout completion until restricted items are handled

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Translation Support

The plugin includes full translation support for:
- WPML
- Polylang
- Standard WordPress translation files

## Hooks and Filters

### Actions
- `ict_mcp_before_restriction_check` - Before checking for restrictions
- `ict_mcp_after_restriction_check` - After checking for restrictions
- `ict_mcp_products_removed` - When restricted products are removed

### Filters
- `ict_mcp_restricted_countries` - Modify restricted countries list
- `ict_mcp_restricted_products` - Modify restricted products list
- `ict_mcp_restriction_message` - Customize restriction message
- `ict_mcp_customer_country` - Override customer country detection

## Support

For support and documentation, please visit our support page.

## Changelog

### 1.0.0
- Initial release
- Country-based product restrictions
- WooCommerce settings integration
- Translation support
- Responsive design

## License

This plugin is licensed under the GPL v2 or later.
