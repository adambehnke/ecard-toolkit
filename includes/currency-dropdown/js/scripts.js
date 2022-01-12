

jQuery(document).ready(function($) {
    var order = getWCAdminOrder();

    $('.currency-options').on('change', function() {
        var select = $(this);
        var selectedOption = select.find('option:selected').val();
        var modal = $('.modal-currency-confirmation');
        var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');
        var currentOption = select.attr('data-current');
        
        order._order_currency = selectedOption;

        if (selectedOption != '' && selectedOption != currentOption && Object.keys(order.items).length > 0) {
            wc_meta_boxes_order_items.block();

            modal.find('.modal-currency').text(selectedOption);
            modal.addClass('processing');
            modal.show();
            
            jQuery.ajax({
                type: "post",
                dataType: "json",
                url: woocommerce_admin_meta_boxes.ajax_url,
                data: {
                    action: "check_order_items_currency_entries", 
                    order: order, 
                    security: woocommerce_admin_meta_boxes.order_item_nonce,
                },
                success: function(response) {
                    order = getWCAdminOrder();

                    if (response.success) {
                        if (response.data.items_to_delete.length > 0) {
                            order.items_to_delete = response.data.items_to_delete;

                            modal.addClass('has-items-to-delete');

                            order = populateItemsToDeleteModalSection(order);
                        } else {
                            order.items_to_delete = {};
                        }
                    }
                }
            }).done(function() {
                modal.removeClass('processing');
                wc_meta_boxes_order_items.unblock();
            });
        }
    });

    $('.modal-currency-confirmation .cancel, .modal-currency-confirmation .close-modal').on('click', function() {
        revertOrderCurrencyDropdown();
    });

    $('.modal-currency-confirmation .confirm').on('click', function() {
        var select = $('.currency-options');
        var selectedOption = select.find('option:selected').val();
        var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');

        order._order_currency = selectedOption;

        wc_meta_boxes_order_items.block();

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: woocommerce_admin_meta_boxes.ajax_url,
            data: {
                action: "recalculate_changed_currency_order", 
                order: order, 
                security: woocommerce_admin_meta_boxes.order_item_nonce,
            },
            success: function(response) {
                if (response.success) {
                    let currencyInput = jQuery('input[name="_order_currency"]');
                    let currencySelect = jQuery('select[name="_order_currency"]');

                    currencyInput.val(selectedOption);
                    currencySelect.attr('data-current', selectedOption);
                }
            }
        }).done(function() {
            wc_meta_boxes_order_items.unblock();
            wc_meta_boxes_order_items.trigger('wc_order_items_reload');
        });
    });

    function getWCAdminOrder() {
        var linkedFeesItems = {};
        var order = {'id': 0, 'items': {}, 'items_to_delete': {}, 'taxable_address': {}};
        var items;
        var itemSelector = '';
        var taxableAddress = orderAddress.getOrderTaxableAddress();

        order.id = woocommerce_admin_meta_boxes.post_id;

        itemSelector += '#woocommerce-order-items tr.item,';
        itemSelector += '#woocommerce-order-items tr.fee,';
        itemSelector += '#woocommerce-order-items tr.shipping';

        items = $(itemSelector);
        items.each(function() {
            let item = $(this);
            let id = item.attr('data-order_item_id');

            order['items'][id] = {};
            
            if (item.hasClass('item')) {
                order['items'][id]['type'] = 'item';
                order['items'][id]['name'] = item.find('.name a').text();
                order['items'][id]['quantity'] = item.find('.quantity .edit input[name^="order_item_qty"]').attr('data-qty');
                
                if (item.hasClass('has-linked-fees')) {
                    var linkedFeesString = item.find('.linked-fees').val();
                    var linkedFees = jQuery.parseJSON(linkedFeesString);
    
                    linkedFees.linked_fees.forEach(element => {
                        linkedFeesItems[element] = id;
                    });
    
                    order['items'][id]['linked_fees'] = linkedFees.linked_fees;
                }
            } else if (item.hasClass('fee')) {
                order['items'][id]['type'] = 'fee';
                order['items'][id]['name'] = item.find('.name .edit input[name^="order_item_name"]').val();
            } else if (item.hasClass('shipping')) {
                order['items'][id]['type'] = 'shipping';
                order['items'][id]['label'] = item.find('.name .edit .shipping_method_name').val();
                order['items'][id]['method'] = item.find('select.shipping_method option:selected').val();
                order['items'][id]['name'] = item.find('.name .edit input.shipping_method_name').val();
            }
        });

        order['taxable_address'] = taxableAddress;

        return order;
    }

    function modifyOrderUrlCurrencyParameter(selectedOption) {
        const url = new URL(window.location.href);

        if (selectedOption != '') {
            url.searchParams.set('currency', selectedOption);
        } else {
            url.searchParams.set('currency', 'USD');
        }

        window.history.replaceState(null, null, url);
    }

    function revertOrderCurrencyDropdown() {
        let currency = jQuery('input[name="_order_currency"]').val();
        let select = jQuery('select.currency-options');

        select.val(currency);
    }

    function populateItemsToDeleteModalSection(order) {
        section = jQuery('.modal-currency-confirmation .items-to-delete');
        html = '<ul>';

        order.items_to_delete.forEach(function(item, index) {
            if (typeof order.items[item] != 'undefined') {
                html += '<li>' + order.items[item].name + '</li>';
            } else {
                /*
                // Remove the deleted item from the order items to delete
                order.items_to_delete = jQuery.grep(order.items_to_delete, function(value) {
                    return value != item;
                });
                */
            }
        });

        html += '</ul>';

        section.append(html);

        return order;
    }
});