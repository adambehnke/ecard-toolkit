jQuery(document).ready(function($) {
    var savingInProcess = false;

    $('.order_data_column input#_billing_city, .order_data_column input#_billing_postcode, .order_data_column input#_shipping_city, .order_data_column input#_shipping_postcode').on('focusout', function() {
        if (!savingInProcess) {
            var order = getWCAdminDraftOrder();

            saveAddressToDraftOrder(order, $(this));
        }
    });

    $('body').on('DOMSubtreeModified', '.order_data_column #select2-_billing_country-container, .order_data_column #select2-_billing_state-container, .order_data_column #select2-_shipping_country-container, .order_data_column #select2-_shipping_state-container', function() {
        if (!savingInProcess) {
            var order = getWCAdminDraftOrder();

            saveAddressToDraftOrder(order, $(this));
        }
    });

    function getWCAdminDraftOrder() {
        var order = {'id': 0, 'taxable_address': {}, 'status': 'auto-draft'};
        var address = orderAddress.getAll();

        order.id = woocommerce_admin_meta_boxes.post_id;
        order.address = address;

        return order;
    }

    function saveAddressToDraftOrder(order, trigger) {
        let triggerContext = getTriggerContext(trigger);

        if (order.address[triggerContext]['allFieldsHaveInfo']) {
            savingInProcess = true;

            showOverlay();
    
            jQuery.ajax({
                type: "post",
                dataType: "json",
                url: woocommerce_admin_meta_boxes.ajax_url,
                data: {
                    action: "save_address_to_draft_order", 
                    order: order, 
                    security: woocommerce_admin_meta_boxes.order_item_nonce,
                },
                success: function(response) {
                    if (response.success) {
    
                    }
                }
            }).done(function() {
                savingInProcess = false;

                hideOverlay();
            });
        }
    }

    function showOverlay() {
        $('.order-address-overlay').fadeIn();
    }

    function hideOverlay() {
        $('.order-address-overlay').fadeOut();
    }

    function getTriggerContext(trigger) {
        let triggerId = trigger.attr('id');

        if (triggerId.indexOf('shipping') >= 0) {
            return 'shipping';
        }

        return 'billing';
    }
});
