/**
 * ITC MCP Frontend JavaScript
 */
(function($) {
    'use strict';
    
    const IctMcpFrontend = {
        
        init: function() {
            this.bindEvents();
            this.checkRestrictions();
        },
        
        bindEvents: function() {
            $(document).on('click', '.ict-mcp-remove-products-btn', this.showConfirmationModal);
            $(document).on('click', '.ict-mcp-modal-close', this.closeModal);
            $(document).on('click', '.ict-mcp-modal', function(e) {
                if (e.target === this) {
                    IctMcpFrontend.closeModal();
                }
            });
            
            // Check restrictions when country changes
            $(document).on('change', '#billing_country, #shipping_country', this.debounce(this.checkRestrictions, 500));
            
            // Check restrictions when cart is updated
            $(document.body).on('updated_cart_totals', this.checkRestrictions);
            $(document.body).on('updated_checkout', this.checkRestrictions);
        },
        
        checkRestrictions: function() {
            const restrictionNotice = $('.ict-mcp-restriction-notice');
            if (restrictionNotice.length === 0) {
                return;
            }
            
            const restrictedProducts = restrictionNotice.data('restricted-products');
            if (!restrictedProducts || restrictedProducts.length === 0) {
                return;
            }
            
            // Add action buttons if not already present
            if (restrictionNotice.find('.ict-mcp-restriction-actions').length === 0) {
                IctMcpFrontend.addActionButtons(restrictionNotice, restrictedProducts);
            }
        },
        
        addActionButtons: function(notice, restrictedProducts) {
            const actionsHtml = `
                <div class="ict-mcp-restriction-actions">
                    <button type="button" class="ict-mcp-btn ict-mcp-btn-danger ict-mcp-remove-products-btn" 
                            data-product-ids='${JSON.stringify(restrictedProducts)}'>
                        Remove Restricted Products
                    </button>
                    <a href="${wc_cart_params.cart_url}" class="ict-mcp-btn ict-mcp-btn-secondary">
                        Continue Shopping
                    </a>
                </div>
            `;
            
            notice.append(actionsHtml);
        },
        
        showConfirmationModal: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const productIds = button.data('product-ids');
            
            // Get product names from the notice
            const productNames = [];
            $('.ict-mcp-restriction-notice ul li').each(function() {
                productNames.push($(this).text().trim());
            });
            
            IctMcpFrontend.createConfirmationModal(productIds, productNames);
        },
        
        createConfirmationModal: function(productIds, productNames) {
            const modalHtml = `
                <div class="ict-mcp-modal" id="ict-mcp-confirmation-modal">
                    <div class="ict-mcp-modal-content">
                        <div class="ict-mcp-modal-header">
                            <div class="ict-mcp-modal-icon">⚠️</div>
                            <h3 class="ict-mcp-modal-title">Remove Restricted Products</h3>
                            <span class="ict-mcp-modal-close">&times;</span>
                        </div>
                        <div class="ict-mcp-modal-body">
                            <div class="ict-mcp-modal-message">
                                <p><strong>${ict_mcp_frontend.messages.confirm_removal}</strong></p>
                                <p>The following products will be removed from your cart:</p>
                            </div>
                            <div class="ict-mcp-modal-products">
                                <ul>
                                    ${productNames.map(name => `
                                        <li>
                                            <span class="ict-mcp-product-icon">🚫</span>
                                            <span class="ict-mcp-product-name">${name}</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                            <div class="ict-mcp-modal-question">
                                <p><strong>Do you want to continue?</strong></p>
                            </div>
                        </div>
                        <div class="ict-mcp-modal-footer">
                            <button type="button" class="ict-mcp-btn ict-mcp-btn-secondary" data-action="cancel">
                                <span class="ict-mcp-btn-icon">✕</span>
                                Cancel
                            </button>
                            <button type="button" class="ict-mcp-btn ict-mcp-btn-danger" data-action="confirm">
                                <span class="ict-mcp-btn-icon">🗑️</span>
                                Yes, Remove Products
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#ict-mcp-confirmation-modal').addClass('show');
            
            // Bind modal events
            $('#ict-mcp-confirmation-modal').on('click', '[data-action="cancel"]', IctMcpFrontend.closeModal);
            $('#ict-mcp-confirmation-modal').on('click', '[data-action="confirm"]', function() {
                IctMcpFrontend.confirmRemoveProducts(productIds);
            });
            
            // Prevent body scroll
            $('body').addClass('ict-mcp-modal-open');
        },
        
        confirmRemoveProducts: function(productIds) {
            const confirmBtn = $('#ict-mcp-confirmation-modal [data-action="confirm"]');
            const originalText = confirmBtn.text();
            
            confirmBtn.prop('disabled', true).html('<span class="ict-mcp-loading"></span>Removing...');
            
            $.ajax({
                url: ict_mcp_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'ict_mcp_remove_restricted_products',
                    product_ids: productIds,
                    nonce: ict_mcp_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        IctMcpFrontend.showSuccessMessage(response.data.message);
                        
                        // Reload page to update cart
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        IctMcpFrontend.showErrorMessage(response.data || 'Error removing products');
                        confirmBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    IctMcpFrontend.showErrorMessage('An error occurred while removing products');
                    confirmBtn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        closeModal: function() {
            $('#ict-mcp-confirmation-modal').removeClass('show');
            setTimeout(function() {
                $('#ict-mcp-confirmation-modal').remove();
                $('body').removeClass('ict-mcp-modal-open');
            }, 300);
        },
        
        showSuccessMessage: function(message) {
            this.showMessage(message, 'success');
        },
        
        showErrorMessage: function(message) {
            this.showMessage(message, 'error');
        },
        
        showMessage: function(message, type) {
            const isSuccess = type === 'success';
            const messageClass = isSuccess ? 'ict-mcp-success-message' : 'ict-mcp-error-message';
            const contentClass = isSuccess ? 'ict-mcp-success-content' : 'ict-mcp-error-content';
            const messageHtml = `
                <div class="${messageClass}">
                    <div class="${contentClass}">
                        <strong>${isSuccess ? 'Success!' : 'Error!'}</strong>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            
            // Remove existing messages
            $('.woocommerce-message, .woocommerce-error, .ict-mcp-success-message, .ict-mcp-error-message').remove();
            
            // Add new message
            if ($('.woocommerce-cart-form').length) {
                $('.woocommerce-cart-form').before(messageHtml);
            } else if ($('.woocommerce-checkout-form').length) {
                $('.woocommerce-checkout-form').before(messageHtml);
            } else {
                $('.ict-mcp-restriction-notice').before(messageHtml);
            }
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $(`.${messageClass}`).offset().top - 100
            }, 500);
            
            // Auto-hide success messages after 5 seconds
            if (isSuccess) {
                setTimeout(function() {
                    $(`.${messageClass}`).fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        IctMcpFrontend.init();
    });
    
    // Add CSS for modal body scroll prevention
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            body.ict-mcp-modal-open {
                overflow: hidden;
            }
        `)
        .appendTo('head');
    
})(jQuery);
