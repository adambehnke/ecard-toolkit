var orderDropdown = {}; 

orderDropdown.getOrderTaxableAddress = function() {
    var country          = '';
    var state            = '';
    var postcode         = '';
    var city             = '';

    if ('shipping' === woocommerce_admin_meta_boxes.tax_based_on) {
        country  = jQuery( '#_shipping_country' ).val();
        state    = jQuery( '#_shipping_state' ).val();
        postcode = jQuery( '#_shipping_postcode' ).val();
        city     = jQuery( '#_shipping_city' ).val();
    }

    if ('billing' === woocommerce_admin_meta_boxes.tax_based_on || !country) {
        country  = jQuery( '#_billing_country' ).val();
        state    = jQuery( '#_billing_state' ).val();
        postcode = jQuery( '#_billing_postcode' ).val();
        city     = jQuery( '#_billing_city' ).val();
    }

    return {
        country:  country,
        state:    state,
        postcode: postcode,
        city:     city
    };
};

orderDropdown.getAttributes = function(node) {
    var attrs = {};

    jQuery.each(node[0].attributes, function (index, attribute) {
        attrs[attribute.name] = attribute.value;
    });

    return attrs;
}

orderDropdown.getSpinnerHtml = function() {
    return '<i class="fa fa-asterisk icon-spinner"></i>';
}

orderDropdown.getLoaderHtml = function() {
    var loadingHtml = '';
        
    loadingHtml += '<div class="loading-container">'
    loadingHtml += orderDropdown.getSpinnerHtml();
    loadingHtml += '<span class="loading-text">Loading options</span>';
    loadingHtml += '</div>';

    return loadingHtml;
}

orderDropdown.getDataFromOrderForm = function(select, singularType = '') {
    if (singularType == '') {
        singularType = select.attr('data-type');
    }
    
    var row = select.closest('.' + singularType);
    var selectedOption = select.find('option:selected');
    var totalsSectionRows = jQuery('table.wc-order-totals tr');
    var totalsSectionLabels = [];
    var data = {
        action  : 'process_added_' + singularType  + '_data',
        dataType: 'json',
        option_id: selectedOption.val(),
        option_name: selectedOption.attr('data-label'),
        order_id: woocommerce_admin_meta_boxes.post_id,
        order_item_id: row.attr('data-order_item_id'),
        taxable_address: orderAddress.getOrderTaxableAddress(),
        attributes: orderDropdown.getAttributes(selectedOption),
        security: woocommerce_admin_meta_boxes.order_item_nonce,
        totals_section_labels: totalsSectionLabels,
        row: row,
        singular_type: singularType
    };

    totalsSectionRows.each(function() {
        data.totals_section_labels.push(jQuery(this).find('.label').text());
    });
    
    if (typeof data.option_name == "undefined") {
        data.option_name = selectedOption.text();
    }

    return data;
}

orderDropdown.makeDataRowEditable = function(data) {
    data.row.addClass(data.singular_type + '-custom');
    data.row.find('.name .view').text('');
    data.row.find('.name .edit input[type="text"]').val('Custom ' + data.singular_type);
    data.row.find('.line_cost .edit input[type="text"]').val(0.00);
    data.row.find('.line_tax .edit input[type="text"]').val(0.00);
    data.row.find('.edit-order-item').click();
}

orderDropdown.saveOrderItem = function(data) {
    var wc_meta_boxes_order_items = jQuery('#woocommerce-order-items');
    var row = data.row;

    delete data.row;

    wc_meta_boxes_order_items.block();

    jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function(response) {
        var parsedResponse = JSON.parse(response);
    
        if (parsedResponse.success) {
            var rowPriceHtml = row.find('.line_cost .woocommerce-Price-currencySymbol').outerHtml();
            var rowTaxesHtml = row.find('.line_tax .woocommerce-Price-currencySymbol').outerHtml();
            var wcOrderTotalsRows = jQuery('.wc-order-totals-items .wc-order-totals').find('tbody > tr');
            var rowElement;

            rowPriceHtml += parsedResponse.price;

            if (typeof parsedResponse.taxes !== "undefined") {
                rowTaxesHtml += parsedResponse.taxes;
            }

            rowBdi = row.find('.line_cost .view bdi');
            rowNoBdiSpan = row.find('.line_cost .view .amount');
                    
            if (rowBdi.length > 0) {
                rowElement = rowBdi;
            } else if (rowNoBdiSpan.length > 0) {
                rowElement = rowNoBdiSpan;
            }
    
            row.find('.name .view').text(data.option_name);
            row.find('.name .edit input[type="text"]').val(data.option_name);
            row.find('.line_cost .edit input[type="text"]').val(parsedResponse.price);

            rowElement.html(rowPriceHtml);

            if (typeof parsedResponse.taxes !== "undefined") {
                row.find('.line_tax .view bdi').html(rowTaxesHtml);
                row.find('.line_tax .edit input[type="text"]').val(parsedResponse.taxes);
            }

            wcOrderTotalsRows.each(function(index) {
                var totalsRow = jQuery(this);
                var totalsRowBdi = totalsRow.find('.total bdi');
                var totalsRowLabel = totalsRow.find('.label').text().toLowerCase();
                var rowAmountHtml = totalsRowBdi.find('.woocommerce-Price-currencySymbol').outerHtml();

                if (totalsRowLabel.indexOf(data.singular_type) != -1 && typeof parsedResponse.order.amount !== "undefined") {
                    rowAmountHtml += parsedResponse.order.amount;
                    totalsRowBdi.html(rowAmountHtml);
                } else if (totalsRowLabel.indexOf('tax') != -1 && typeof parsedResponse.order.taxes !== "undefined") {
                    rowAmountHtml += parsedResponse.order.taxes;
                    totalsRowBdi.html(rowAmountHtml);
                } else if (totalsRowLabel.indexOf(' total') != -1 && typeof parsedResponse.order.total !== "undefined") {
                    rowAmountHtml += parsedResponse.order.total;
                    totalsRowBdi.html(rowAmountHtml);
                }
            });

            if (typeof parsedResponse.totals_row_html != "undefined") {
                jQuery(document).trigger('order::totalsRowHtmlFound', [parsedResponse.totals_row_html, data.singular_type]);
            }

            jQuery(document).trigger('order::orderItemSaved', [data]);
        } else {
            window.alert(parsedResponse.error);
        }
    }).complete(function() {
        row.removeClass().addClass(data.singular_type);
        data.row = row;
        orderDropdown.turnOffRowEditMode(row);

        if (orderActions.orderIsBlockedBy === '') {
            wc_meta_boxes_order_items.unblock();
        }
    });
}

orderDropdown.populateDataRowElements = function(data) {
    var rowPriceHtml = data.row.find('.woocommerce-Price-currencySymbol').html();

    rowPriceHtml += '0.00';

    data.row.find('.name .view').text('');
    data.row.find('.name .edit input[type="text"]').val('');
    data.row.find('.line_cost .view bdi').html(rowPriceHtml);
    data.row.find('.line_cost .edit input[type="text"]').val(0.00);
    data.row.find('.line_tax .view bdi').html(rowPriceHtml);
    data.row.find('.line_tax .edit input[type="text"]').val(0.00);
}

orderDropdown.turnOffRowEditMode = function(row) {
    row.find('.name .view:nth-of-type(1)').css('display', 'block');
    row.find('.name .edit').css('display', 'none');
    row.find('.line_cost .view').css('display', 'block');
    row.find('.line_cost .edit').css('display', 'none');
    row.find('.line_tax .view').css('display', 'block');
    row.find('.line_tax .edit').css('display', 'none');
}

jQuery(document).ready(function($) {
    var intervals = {};

    // Iterate through dropdowns and create JS Intervals for each
    $.each(dropdownTypes, function(key, value){
        var buttonsIntervalKey = 'setup' + key.toUpperCase() + 'ButtonsInt';
        var classesIntervalKey = 'setup' + key.toUpperCase() + 'ClassesInt';

        intervals[buttonsIntervalKey] = setInterval(function() {
            setupButtonsInterval(key, intervals[buttonsIntervalKey])
            }, 200
        );

        intervals[classesIntervalKey] = setInterval(function() {
            setupClassesInterval(key, intervals[classesIntervalKey])
            }, 200
        );
    });

    // On reload setup the buttons and classes again
    jQuery('#woocommerce-order-items').on('wc_order_items_reloaded', function() {
        $.each(dropdownTypes, function(key, value){
            var parentButtons = $('.wc-order-add-item');
            var parentClasses = $('#order_' + value + '_line_items');

            setupButtons(parentButtons, key);
            setupClasses(parentClasses, key);
        });
    });

    jQuery('#woocommerce-order-items').on('change', '.dropdown-options', function() {
        var select = $(this); 
        var data = orderDropdown.getDataFromOrderForm(select);

        if (data.attributes['data-action'] == 'save') {
            orderDropdown.saveOrderItem(data);
        } else if (data.attributes['data-action'] == 'edit') {
            orderDropdown.makeDataRowEditable(data);
        } else {
            orderDropdown.populateDataRowElements(data);
        }
    });

    $('#woocommerce-order-items').on('click', '.edit-order-item', function() {
        var toggle = $(this);

        toggle.closest('tr').addClass('dropdown-row-edit');
    });

    function setupButtonsInterval(type, interval) {
        var parent = $('.wc-order-add-item');

        if (!parent.hasClass(type + '-buttons-setup')) {
            setupButtons(parent, type);
        } else {
            clearInterval(interval);
        }
    }

    function setupClassesInterval(type, interval) {
        var parent = $('#order_' + dropdownTypes.type  +'_line_items');

        if (!parent.hasClass(type + '-classes-setup')) {
            setupClasses(parent, type);
        } else {
            clearInterval(interval);
        }
    }

    function setupButtons(parent, type) {
        parent.addClass(type + '-buttons-setup');
        parent.find('.add-order-' + type).text('Add custom ' + dropdownTypes.type);
    }

    function setupClasses(parent, type) {
        var rows = parent.find('tr.' + dropdownTypes.type);

        rows.each(function() {
            var row = $(this);
            var itemId = row.attr('data-order_item_id');

            row.addClass(dropdownTypes.type + '-' + itemId);
        });

        parent.addClass(type + '-classes-setup');
    }
});

jQuery.fn.outerHtml = function() {
    return jQuery('<div />').append(this.eq(0).clone()).html();
};
