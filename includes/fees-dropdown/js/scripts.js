jQuery(document).ready(function($) {
    $('#woocommerce-order-items').on('click', '.add-order-fee-select', function() {
        var wc_meta_boxes_order_items = $('#woocommerce-order-items');

        window.wcTracks.recordEvent('order_edit_add_fee_click', {
            order_id: woocommerce_admin_meta_boxes.post_id,
            status: $('#order_status').val()
        });

        var data = $.extend({}, {taxable_address: orderAddress.getOrderTaxableAddress()}, {
            action  : 'add_order_fee_item_with_dropdown',
            dataType: 'json',
            order_id: woocommerce_admin_meta_boxes.post_id,
            security: woocommerce_admin_meta_boxes.order_item_nonce,
            amount: 0
        });

        wc_meta_boxes_order_items.block();

        $.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
            if (response.success) {
                wc_meta_boxes_order_items.find('.inside').empty();
                wc_meta_boxes_order_items.find('.inside').append(response.data.html);
                wc_meta_boxes_order_items.trigger('wc_order_items_reloaded');
                wc_meta_boxes_order_items.find('#order_fee_line_items > .fee:last-child').addClass('fee-added');
                wc_meta_boxes_order_items.unblock();
                window.wcTracks.recordEvent('order_edit_added_fee', {
                    order_id: data.order_id,
                    status: $('#order_status').val()
                });
            } else {
                window.alert(response.data.error);
            }
        }).always(function() {
            wc_meta_boxes_order_items.unblock();
        });
    });

    $('.add-order-fee-custom').on('click', function() {
        $('.add-order-fee').click();
    });

    $('#woocommerce-order-items').on('click', '.edit-order-item', function() {
        var toggle = $(this);

        toggle.closest('.fee').addClass('fee-edit');
    });

    $(document).on('order::totalsRowHtmlFound', function(event, html, type) {
        if (type == 'fee' && html != '') {
            $('.wc-order-totals:nth-child(1) tr:nth-child(1)').after(html);
        }
    });

    $(document).on('order::orderItemSaved', function(event, data) {
        if (data.singular_type === 'fee') {
            orderActions.orderIsBlockedBy = 'fees-dropdown';

            var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');
            var data = {
                action:   'woocommerce_calc_line_taxes',
                order_id: woocommerce_admin_meta_boxes.post_id,
                items:    $('table.woocommerce_order_items :input[name], .wc-order-totals-items :input[name]').serialize(),
                security: woocommerce_admin_meta_boxes.calc_totals_nonce
            };

            wc_meta_boxes_order_items.block();            

            $.ajax({
                url:  woocommerce_admin_meta_boxes.ajax_url,
                data: data,
                type: 'POST',
                success: function(response) {
                    $('#woocommerce-order-items').find('.inside').empty();
                    $('#woocommerce-order-items').find('.inside').append(response);

                    $(document.body).trigger('order-totals-recalculate-success', response);
                },
                complete: function(response) {
                    $(document.body).trigger('order-totals-recalculate-complete', response);

                    wc_meta_boxes_order_items.unblock();

                    orderActions.orderIsBlockedBy = '';

                    window.wcTracks.recordEvent('order_edit_recalc_totals', {
                        order_id: data.post_id,
                        OK_cancel: 'OK',
                        status: $('#order_status').val()
                    });
                }
            });
        }
    });

    // Linked fees submodule    
    var preprocessed = false;

    $('#woocommerce-order-items').on('click', '.delete-order-item', function(e) {
        var toggle = $(this);
        var item = toggle.closest('tr');
        var order = {
            'id': woocommerce_admin_meta_boxes.post_id, 
            'linked_fees': {}
        };

        e.stopImmediatePropagation();
        e.preventDefault();

        var answer = window.confirm(woocommerce_admin_meta_boxes.remove_item_notice);

        if (answer) {
            if (preprocessed === false) {  
                    preprocessed = true;
            
                    if (item.hasClass('has-linked-fees')) {
                        var hiddenInput = item.find('input.linked-fees');
                        var linkedFees = JSON.parse(hiddenInput.val());
                        var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');    
                        
                        order['linked_fees'] = linkedFees.linked_fees;
            
                        wc_meta_boxes_order_items.block();
            
                        jQuery.ajax({
                            type: "post",
                            dataType: "json",
                            url: woocommerce_admin_meta_boxes.ajax_url,
                            data: {
                                action: "remove_order_item_linked_fees", 
                                order: order, 
                                security: woocommerce_admin_meta_boxes.order_item_nonce,
                            },
                            success: function(response) {
                                if (response == '1') {
                                    removeItemFromOrder(order['id'], item);
                                } else {
                                    console.log('Fee was not deleted');
                                }
                            },
                            complete: function() {
                                item.addClass('preprocessed');
                                wc_meta_boxes_order_items.unblock();
                            }
                        });
                    }
            } else {
                preprocessed = false;
            }

            removeItemFromOrder(order['id'], item);
        }
    });

    addLinkedFeesItemClasses();

    jQuery(document).on('order::itemsReloaded', function() {
        addLinkedFeesItemClasses();
    });

    jQuery('#woocommerce-order-items').on('wc_order_items_reloaded', function() {
        addLinkedFeesItemClasses();
    });

    function addLinkedFeesItemClasses() {
        $('.post-type-shop_order #order_line_items .item').each(function() {
            var item = $(this);
            var hiddenInput = item.find('input.linked-fees');
    
            if (hiddenInput.length > 0) {
                item.addClass('has-linked-fees');
            }
        });
    }

    function removeItemFromOrder(orderId, item) {
        var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');
        var itemId = item.attr('data-order_item_id');
    
        wc_meta_boxes_order_items.block();

        jQuery.ajax({
            type: "post",
            url: woocommerce_admin_meta_boxes.ajax_url,
            data: {
                action: "remove_item_from_order", 
                order_id: orderId, 
                item_to_delete: itemId, 
                security: woocommerce_admin_meta_boxes.order_item_nonce,
            },
            success: function(response) {
                wc_meta_boxes_order_items.find('.inside').empty();
                wc_meta_boxes_order_items.find('.inside').append(response.data.html);
                
                $(document.body).trigger('order-totals-recalculate-complete');

                wc_meta_boxes_order_items.block();
            },
            complete: function() {
                if (orderActions.orderIsBlockedBy === '') {
                    wc_meta_boxes_order_items.unblock();
                }
            }
        });
    }
});
