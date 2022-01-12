orderDropdown.addOrderShippingItemWithDropdown = function(data) {
    var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');

    wc_meta_boxes_order_items.block();

    jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
        if (response.success) {
            wc_meta_boxes_order_items.find('.inside').empty();
            wc_meta_boxes_order_items.find('.inside').append(response.data.html);
            wc_meta_boxes_order_items.trigger('wc_order_items_reloaded');
            wc_meta_boxes_order_items.find('#order_shipping_line_items > .shipping:last-child').addClass('shipping-added');
            window.wcTracks.recordEvent('order_edit_added_shipping', {
                order_id: data.post_id,
                status: jQuery('#order_status').val()
            });
        } else {
            window.alert(response.data.error);
        }
    }).always(function() {
        wc_meta_boxes_order_items.unblock();
    });
}

jQuery(document).ready(function($) {
    var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');

    $('#woocommerce-order-items').on('click', '.add-order-shipping-select', function() {   
        var orderStatus = $('input[name="original_post_status"]').val();
        var data = $.extend({}, {taxable_address: orderAddress.getOrderTaxableAddress()}, {
            action  : 'add_order_shipping_item_with_dropdown',
            dataType: 'json',
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
            amount: 0
        });

        if (orderStatus == 'auto-draft') {
            var orderAddresses = orderAddress.getAll();

            data['orderAddresses'] = orderAddresses;
        }

        orderDropdown.addOrderShippingItemWithDropdown(data);
    });

    jQuery('#woocommerce-order-items').on('change', '.shipping-options', function() {
        var select = $(this);
        var selectedOption = select.find('option:selected');
        var row = select.closest('.shipping');
        var selectedOptionLabel = selectedOption.attr('data-label');
        var selectedOptionPrice = selectedOption.attr('data-price');

        row.find('.name .view').text(selectedOptionLabel);
        row.find('.name .edit .shipping_method_name').val(selectedOptionLabel);
        row.find('.line_cost .edit .wc_input_price').val(selectedOptionPrice);
    });

    $('#woocommerce-order-items').on('change', '.shipping .name .shipping_method', function() {
        var select = $(this);
        var selectedOption = select.find('option:selected');
        var row = select.closest('.shipping');

        hideRowEditingField(row);

        if (isShippingTypeSelected('fedex', select)) {
            row.addClass('shipping-selected-fedex').removeClass('shipping-selected-no-additional');

            if (!row.hasClass('shipping-additional-options-loaded')) {
                row.find('.edit-order-item').click();
            }
        } else {
            var selectedOptionLabel = selectedOption.text();

            row.removeClass('shipping-selected-fedex').addClass('shipping-selected-no-additional');
            row.find('.name .view').text(selectedOptionLabel);
            row.find('.name .edit .shipping_method_name').val(selectedOptionLabel);
            row.find('.edit-order-item').click();
            row.find('.line_cost .edit input[type="text"]').val(0);

            if (isShippingTypeSelected('other', select)) {
                showRowEditingField(row);
            }
        }
    });

    $('#woocommerce-order-items').on('click', '.shipping .edit-order-item', function() {
        var toggle = $(this);
        var row = toggle.closest('.shipping');
        var nameTd = row.find('td.name');
        var shippingMethodSelect = $('.shipping_method');
        var loadingHtml = orderDropdown.getLoaderHtml();

        if (!row.hasClass('shipping-added') && isShippingTypeSelected('fedex', shippingMethodSelect)) {
            var data = $.extend({}, {taxable_address: orderAddress.getOrderTaxableAddress()}, {
                action: 'add_order_shipping_dropdown',
                order_id: woocommerce_admin_meta_boxes.post_id,
                security: woocommerce_admin_meta_boxes.order_item_nonce,
            });

            nameTd.append(loadingHtml).addClass('loading');

            jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
                if (response.success) {
                    nameTd.append(response.data);
                    row.addClass('shipping-additional-options-loaded');
                } else {
                    window.alert(response.data.error);
                }
            }).always(function() {
                nameTd.remove('.loading-container').removeClass('loading');
            });
        } 
    });

    $('#woocommerce-order-items').on('click', '.save-action-custom', function() {
        var modal = $('.modal-shipping-confirmation');
        var shippingItems = $('#order_shipping_line_items .shipping:not(:last-child)');
        var deleteContainer = $('.modal-confirmation .shipping-items-to-delete');

        if (shippingItems.length > 0) {
            deleteContainer.empty();
            modal.fadeIn();

            shippingItems.each(function() {
                var item = $(this);
                var name = item.find('.name .view').text();
                var price = item.find('.line_cost .view').html();

                deleteContainer.append('<ul><li>' + name + '(' + price + ')' + '</li></ul>');
                modal.addClass('has-items-to-delete');
            });
        } else {
            /*
            var data = {
                action  : 'add_single_shipping_item',
                dataType: 'json',
                order_id: woocommerce_admin_meta_boxes.post_id,
                security: woocommerce_admin_meta_boxes.order_item_nonce,
            };

            nameTd.append(loadingHtml).addClass('loading');

            jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
                if (response.success) {
                    nameTd.append(response.data);
                    row.addClass('shipping-additional-options-loaded');
                } else {
                    window.alert(response.data.error);
                }
            }).always(function() {
                nameTd.remove('.loading-container').removeClass('loading');
            });
            console.log('SAVING');
            wc_meta_boxes_order_items.block();
            */
            $('button.save-action').trigger('click');
        }
    });

    $('.modal-shipping-confirmation .confirm').on('click', function() {
        var shippingItems = $('#order_shipping_line_items .shipping:not(:last-child)');
        var shippingItemToSave = $('#order_shipping_line_items .shipping.shipping-added');
        var itemsToDelete = [];

        shippingItems.each(function() {
            var item = $(this);
            var itemId = item.attr('data-order_item_id');

            itemsToDelete.push(itemId);
        });

        var data = $.extend({}, {taxable_address: orderAddress.getOrderTaxableAddress()}, {
            action  : 'remove_shipping_items_from_order',
            dataType: 'json',
            order_id: woocommerce_admin_meta_boxes.post_id,
            items_to_delete: itemsToDelete,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
        });

        wc_meta_boxes_order_items.block();

        jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
            if (response.success) {
                shippingItems.remove();
            } else {
                window.alert(response.data.error);
            }
        }).always(function() {
            if (shippingItemToSave.length == 1) {
                var select, selectedOption, data;

                if (shippingItemToSave.hasClass('shipping-selected-no-additional')) {
                    select = shippingItemToSave.find('.shipping_method');
                } else {
                    select = shippingItemToSave.find('.shipping-options');
                }

                selectedOption = select.find('option:selected');
                data = orderDropdown.getDataFromOrderForm(select, 'shipping');

                if (typeof data.attributes['data-label'] == 'undefined') {
                    data.attributes['data-label'] = selectedOption.text();
                }

                if (typeof data.attributes['data-method-id'] == 'undefined') {
                    data.attributes['data-method-id'] = selectedOption.val();
                }

                if (typeof data.attributes['data-price'] == 'undefined') {
                    data.attributes['data-price'] = shippingItemToSave.find('.line_cost .edit input[type="text"]').val();
                }

                orderDropdown.saveOrderItem(data);
                
                $('.cancel-action').trigger('click');
            }

            wc_meta_boxes_order_items.unblock();

            jQuery(document).trigger('order::itemsReloaded');
        });
    });

    $('#woocommerce-order-items').on('click', '.delete-order-item-shipping', function(e) {
        e.stopImmediatePropagation();
        e.preventDefault();
        
        wc_meta_boxes_order_items.block();

        var trigger = $(this);
        var item = trigger.closest('.shipping');

        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: woocommerce_admin_meta_boxes.ajax_url,
            data: {
                action: "remove_item_from_order", 
                order_id: woocommerce_admin_meta_boxes.post_id, 
                item_to_delete: item.attr('data-order_item_id'), 
                security: woocommerce_admin_meta_boxes.order_item_nonce,
            },
            success: function(response) {
                if (response.success) {
                    wc_meta_boxes_order_items.find('.inside').empty();
                    wc_meta_boxes_order_items.find('.inside').append(response.data.html);
                    wc_meta_boxes_order_items.trigger('wc_order_items_reloaded');
                } else {
                    window.alert(response.data.error);
                }

                wc_meta_boxes_order_items.unblock();
            },
            always: function() {
                wc_meta_boxes_order_items.unblock();
            }
        });
    });

    function addShippingItemDeleteButton() {
        $('#order_shipping_line_items tr').each(function() {
            let item = $(this);

            item.find('.wc-order-edit-line-item-actions').append('<a class="delete-order-item-shipping" href="#"></a>');
        });
    }

    function isShippingTypeSelected(type, select) {
        var selectedOption = select.find('option:selected');
        var optionValue = selectedOption.val();
        var shippingValues = {'fedex': 'wf_fedex_woocommerce_shipping', 'other': 'other'};

        if (optionValue == shippingValues[type]) {
            return true;
        } else {
            return false;
        }
    }

    function showRowEditingField(row) {
        row.find('.edit .shipping_method_name').show();
    }

    function hideRowEditingField(row) {
        row.find('.edit .shipping_method_name').hide();
    }
});
