/**
 * ITC MCP Admin JavaScript
 */
(function($) {
    'use strict';
    
    const IctMcpAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initProductSearch();
        },
        
        bindEvents: function() {
            $(document).on('change', '#ict_mcp_restricted_countries', this.handleCountryChange);
            $(document).on('change', '#ict_mcp_restricted_products', this.handleProductChange);
            $(document).on('click', '.ict-mcp-remove-btn', this.handleRemoveProducts);
            $(document).on('click', '.ict-mcp-modal-close', this.closeModal);
            $(document).on('click', '.ict-mcp-modal', function(e) {
                if (e.target === this) {
                    IctMcpAdmin.closeModal();
                }
            });
        },
        
        initProductSearch: function() {
            if (typeof $.fn.select2 !== 'undefined') {
                $('#ict_mcp_restricted_products').select2({
                    placeholder: 'Search for products...',
                    allowClear: true,
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page,
                                action: 'ict_mcp_search_products'
                            };
                        },
                        processResults: function(data, params) {
                            console.log('AJAX Response:', data);
                            const results = [];
                            if (data && data.success && data.data) {
                                $.each(data.data, function(index, item) {
                                    results.push({
                                        id: item.id,
                                        text: item.text
                                    });
                                });
                            } else {
                                console.log('No results found or error in response');
                            }
                            return {
                                results: results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            }
        },
        
        handleCountryChange: function() {
            const selectedCountries = $(this).val() || [];
            console.log('Selected countries:', selectedCountries);
            
            // Update product restrictions based on country selection
            IctMcpAdmin.updateProductRestrictions(selectedCountries);
        },
        
        handleProductChange: function() {
            const selectedProducts = $(this).val() || [];
            console.log('Selected products:', selectedProducts);
        },
        
        handleRemoveProducts: function(e) {
            e.preventDefault();
            
            const productIds = $(this).data('product-ids');
            const productNames = $(this).data('product-names');
            
            IctMcpAdmin.showConfirmationModal(productIds, productNames);
        },
        
        showConfirmationModal: function(productIds, productNames) {
            const modalHtml = `
                <div class="ict-mcp-modal" id="ict-mcp-confirmation-modal">
                    <div class="ict-mcp-modal-content">
                        <div class="ict-mcp-modal-header">
                            <h3 class="ict-mcp-modal-title">Confirm Removal</h3>
                            <span class="ict-mcp-modal-close">&times;</span>
                        </div>
                        <div class="ict-mcp-modal-body">
                            <p>Are you sure you want to remove these restricted products from your cart?</p>
                            <ul>
                                ${productNames.map(name => `<li>${name}</li>`).join('')}
                            </ul>
                        </div>
                        <div class="ict-mcp-modal-footer">
                            <button type="button" class="ict-mcp-btn ict-mcp-btn-secondary" data-action="cancel">Cancel</button>
                            <button type="button" class="ict-mcp-btn ict-mcp-btn-danger" data-action="confirm">Remove Products</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#ict-mcp-confirmation-modal').show();
            
            // Bind modal events
            $('#ict-mcp-confirmation-modal').on('click', '[data-action="cancel"]', this.closeModal);
            $('#ict-mcp-confirmation-modal').on('click', '[data-action="confirm"]', function() {
                IctMcpAdmin.confirmRemoveProducts(productIds);
            });
        },
        
        confirmRemoveProducts: function(productIds) {
            $.ajax({
                url: ict_mcp_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'ict_mcp_remove_restricted_products',
                    product_ids: productIds,
                    nonce: ict_mcp_frontend.nonce
                },
                beforeSend: function() {
                    $('[data-action="confirm"]').prop('disabled', true).text('Removing...');
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to update cart
                        window.location.reload();
                    } else {
                        alert('Error removing products: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while removing products.');
                },
                complete: function() {
                    IctMcpAdmin.closeModal();
                }
            });
        },
        
        closeModal: function() {
            $('#ict-mcp-confirmation-modal').remove();
        },
        
        updateProductRestrictions: function(countries) {
            // This could be used to dynamically update product restrictions
            // based on country selection in the future
            console.log('Updating product restrictions for countries:', countries);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        IctMcpAdmin.init();
    });
    
})(jQuery);
