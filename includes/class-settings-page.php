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
        
        // Handle WYSIWYG field separately
        $this->output_wysiwyg_field();
        
        $settings = $this->get_settings();
        WC_Admin_Settings::output_fields($settings);
    }
    
    private function output_wysiwyg_field(): void {
        $settings = Ict_Mcp_Settings::get_settings();
        $value = $settings['restriction_message'];
        
        echo '<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="ict_mcp_restriction_message">' . esc_html__('Restriction Message', 'itc-woo-product-sell-restrict') . '</label>
                <span class="woocommerce-help-tip" data-tip="' . esc_attr__('Customize the message shown to customers when restricted products are in their cart.', 'itc-woo-product-sell-restrict') . '"></span>
            </th>
            <td class="forminp">
                <div class="ict-mcp-wysiwyg-container">';
        
        wp_editor($value, 'ict_mcp_restriction_message', [
            'textarea_name' => 'ict_mcp_restriction_message',
            'textarea_rows' => 10,
            'media_buttons' => false,
            'teeny' => true,
            'tinymce' => [
                'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,blockquote,|,undo,redo',
                'toolbar2' => '',
                'toolbar3' => '',
                'menubar' => false,
                'statusbar' => false,
                'resize' => false,
                'wp_autoresize_on' => false,
                'paste_as_text' => true,
                'paste_auto_cleanup_on_paste' => true,
                'paste_remove_styles' => true,
                'paste_remove_styles_if_webkit' => true,
                'paste_strip_class_attributes' => 'all',
                'paste_remove_spans' => true,
                'paste_remove_empty_paragraphs' => true,
                'forced_root_block' => false,
                'force_p_newlines' => false,
                'remove_linebreaks' => false,
                'convert_newlines_to_brs' => true,
                'verify_html' => true,
                'valid_elements' => 'p,br,strong,b,em,i,u,s,strike,ul,ol,li,blockquote,div',
                'valid_children' => 'p[strong|em|u|s|br],ul[li],ol[li],li[p|strong|em|u|s|br|ul|ol],blockquote[p|strong|em|u|s|br|ul|ol],div[p|strong|em|u|s|br|ul|ol|blockquote]',
                'invalid_elements' => 'script,object,embed,link,style,form,input,textarea,button,select,option,label,fieldset,legend,table,tr,td,th,tbody,thead,tfoot,caption,col,colgroup,iframe,frame,frameset,noframes,base,meta,title,head,body,html,applet,area,basefont,bgsound,blink,button,canvas,caption,center,dir,font,frame,frameset,isindex,listing,marquee,menu,multicol,nextid,nobr,noembed,noframes,noscript,plaintext,pre,rt,ruby,s,strike,tt,u,wbr,xmp'
            ],
            'quicktags' => [
                'buttons' => 'strong,em,ul,ol,li,blockquote'
            ]
        ]);
        
        echo '                </div>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // Ensure save button is enabled when WYSIWYG content changes
                    function enableSaveButton() {
                        $(".woocommerce-save-button").prop("disabled", false);
                        $("form.woocommerce-settings-form").addClass("has-changes");
                    }
                    
                    // Bind to TinyMCE events
                    if (typeof tinymce !== "undefined") {
                        tinymce.on("AddEditor", function(e) {
                            if (e.editor.id === "ict_mcp_restriction_message") {
                                e.editor.on("input change keyup paste undo redo", enableSaveButton);
                                e.editor.on("show hide", enableSaveButton);
                                
                                // Handle Enter key to create line breaks instead of paragraphs
                                e.editor.on("keydown", function(event) {
                                    if (event.keyCode === 13) { // Enter key
                                        event.preventDefault();
                                        e.editor.execCommand("mceInsertContent", false, "<br><br>");
                                        enableSaveButton();
                                    }
                                });
                            }
                        });
                        
                        // Also bind to existing editor if already loaded
                        setTimeout(function() {
                            var editor = tinymce.get("ict_mcp_restriction_message");
                            if (editor) {
                                editor.on("input change keyup paste undo redo", enableSaveButton);
                                editor.on("show hide", enableSaveButton);
                                
                                // Handle Enter key to create line breaks instead of paragraphs
                                editor.on("keydown", function(event) {
                                    if (event.keyCode === 13) { // Enter key
                                        event.preventDefault();
                                        editor.execCommand("mceInsertContent", false, "<br><br>");
                                        enableSaveButton();
                                    }
                                });
                            }
                        }, 500);
                    }
                    
                    // Bind to textarea changes (text mode)
                    $("#ict_mcp_restriction_message").on("input change keyup", enableSaveButton);
                    
                    // Ensure content is saved on form submission
                    $("form.woocommerce-settings-form").on("submit", function() {
                        if (typeof tinymce !== "undefined") {
                            tinymce.triggerSave();
                        }
                    });
                });
                </script>
            </td>
        </tr>';
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
            wp_kses_post($_POST['ict_mcp_restriction_message']) : '';
        
        update_option('ict_mcp_settings', [
            'restricted_countries' => $restricted_countries,
            'restricted_products' => $restricted_products,
            'restriction_message' => $restriction_message
        ]);
        
        // Clear any caches
        wp_cache_delete('ict_mcp_settings', 'options');
    }
}
