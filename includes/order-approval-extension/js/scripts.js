jQuery(function($) {
    // Don't redirect to cart in the Approval flow
    if ($('body').hasClass('order-approval-flow')) {
        wc_add_to_cart_params.cart_redirect_after_add = 'no';
    }

    modifyAddProductsPageElements();

    var flow = getUrlParameter('flow');
    var showCouponNotice = false;

    if (flow == 'order-approval') {
        $('body').addClass('order-approval');
    }

    $('#order_review').on('click', '.order-review-quantity-control', function() {
        var control = $(this);
        var action = control.attr('data-action');
        var parent = control.closest('.order-data');
        var order = {'id': 0, 'items': {}};
        var dots = null;

        if (action == 'edit') {
            parent.addClass('active');
        } else if (action == 'save') {
            var nonce = control.attr('data-nonce');

            order['id'] = control.parent().attr('data-order');

            $('.item-changed').each(function() {
                var item = $(this);
                var orderItemId = item.attr('data-id');
                var orderItemKey = item.attr('data-key');
                var productId = item.attr('data-product-id');
                var selectChanged = item.find('.select-changed');

                order['items'][orderItemId] = {};
                order['items'][orderItemId]['key'] = orderItemKey;

                selectChanged.each(function() {
                    var select = $(this);
                    var selectedOption = select.find('option:selected');
                    var orderItemValue = selectedOption.val();
    
                    if (select.hasClass('order-review-quantity-edit')) {
                        var orderItemQuantityPrice = selectedOption.attr('data-price');
    
                        order['items'][orderItemId]['quantity'] = orderItemValue;
                        order['items'][orderItemId]['quantity_price'] = orderItemQuantityPrice;
                    } else if (select.hasClass('order-review-finish-edit')) {
                        order['items'][orderItemId]['finish'] = orderItemValue;
                    } else if (select.hasClass('order-review-production-time-edit')) {
                        var feeItemId = select.attr('data-item-id');

                        order['items'][orderItemId]['production_time'] = orderItemValue;
                        order['items'][orderItemId]['product_id'] = productId;
                        order['save_production_time_change_to_cart'] = orderItemKey;

                        if (typeof feeItemId  !== "undefined") {
                            order['items'][orderItemId]['production_time_id'] = feeItemId;
                        }
                    }
                });

            });

            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : wc_add_to_cart_params.ajax_url,
                data : {
                    action: "persist_order_review_data", 
                    order: order, 
                    nonce: nonce
                },
                beforeSend: function() {
                    parent.removeClass('active').addClass('saving');
                    dots = window.setInterval(function() {
                        var wait = document.getElementById("order-review-quantity-control-save");

                        if (wait.innerHTML.length > 9) {
                            wait.innerHTML = "Saving";
                        } else {
                            wait.innerHTML += ".";
                        }
                    }, 250);
                },
                success: function(response) {
                    if (response == 1) {
                        $('body').trigger('update_checkout');
                        parent.removeClass('saving');
                        clearInterval(dots);
                    }
                }
            });
        }
    });

    if (document.getElementById('order_approval_pay_button')) {
        jQuery('.wc_payment_methods.payment_methods').hide();
        jQuery(document).on('click', '#order_approval_pay_button, #order-review-quantity-control-add', function () {
            var button = $(this);
            var form = jQuery('form.woocommerce-checkout');
            var formData = getValidatedCheckoutFormData(form);
            var id = button.attr('id');
            var message = '';
            var messageContainer = jQuery('.order-approval-flow .woocommerce > .woocommerce-notices-wrapper');
            var formFieldContainers = jQuery('.order-approval-flow .woocommerce-shipping-fields .form-row, .order-approval-flow .woocommerce-billing-fields .form-row');

            messageContainer.removeClass('has-state has-error').html(message);
            formFieldContainers.removeClass('woocommerce-invalid');

            if (formData.fieldsToFillOut.length == 0 && !button.hasClass('active')) {
                formData.push({name: 'action', value: 'update_order_approval_order_details'});
                formData.push({name: 'order_id', value: order_id_update_existing});
    
                if (id == 'order-review-quantity-control-add') {
                    formData.push({name: 'add_products', value: 1});
                }

                button.addClass('active');
                
                jQuery.post(admin_url_order_update, formData, function (res) {
                    window.location.href = res;
                });
            } else {
                jQuery.each(formData.fieldsToFillOut, function(index, value) {
                    let fieldContainer = jQuery('p#' + value + '_field');
                    let label = fieldContainer.find('label');
                    let text = label.text();
                    let fieldType = '';
                    let cleanedText = '';

                    fieldContainer.addClass('woocommerce-invalid');
                    cleanedText = text.replace('*','');
                    
                    if (value.indexOf('billing') != -1) {
                        fieldType = 'Billing ';
                    } else if (value.indexOf('shipping') != -1) {
                        fieldType = 'Shipping ';
                    }

                    message += '<p><strong>' + fieldType + cleanedText + '</strong>is a required field.</p>';

                    $('html, body').animate({
                        scrollTop: jQuery('.order-approval-flow .breadcrumb-title-wrapper').offset().top
                    }, 300);
                });

                messageContainer.addClass('has-state has-error').html(message);
            }
        });
    }

    $('#order_review').on('change', '.order-review-edit', function() {
        var select = $(this);
        var item = select.closest('.cart_item');
        var currentValue = select.attr('data-current');
        var selectedValue = select.find('option:selected').val();

        if (currentValue != selectedValue) {
            select.addClass('select-changed');
            item.addClass('item-changed');
        } else {
            select.removeClass('select-changed');
            item.removeClass('item-changed');
        }
    });

    $('.page-add-products').on('click', '.add-to-order', function(e) {
        e.preventDefault();

        var link = $(this);
        var listing = link.closest('.add-products-listing');
        var product = link.closest('.product');
        var order = {'id': 0, 'item': {}};
        var nonce = listing.attr('data-nonce');
        
        order['id'] = listing.attr('data-order');
        order['item']['product_id'] = link.attr('data-product_id');
        order['item']['quantity'] = product.find('.quantity_select select').val();

        link.addClass('active');

        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : wc_add_to_cart_params.ajax_url,
            data : {
                action: "add_product_item_to_order", 
                order: order, 
                nonce: nonce
            },
            success: function(response) {
                if (response == 1) {
                    html = buildMessageHtml('Product added to order', 'check-circle');

                    populateAndAnimateMessageWindow('.page-add-products .product.post-' + order['item']['product_id'] + ' .order-approval-message-window', html);
                }

                setTimeout(function() {
                    link.removeClass('active');
                }, 300);
            }
        });
    });

    $('.order-approval-flow.single-product').on('click', '.single_add_to_cart_button', function(e) {
        e.preventDefault();

        var link = $(this);
        var product = $('.product');
        var order = {'id': 0, 'item': {}};
        var nonce = orderApprovalData.singleNonce;
        var addons = $('.wc-pao-addon');
        var formData = new FormData();
        var files = $('.wc-pao-addon-file-upload').prop('files');
        
        order['id'] = getUrlParameter('order');        
        order['item']['product_id'] = link.val();
        order['item']['quantity'] = product.find('.quantity_select select').val();

        if(typeof files !== "undefined" && files.length > 0){
            formData.append('file', files[0]);
        }

        if (addons.length > 0) {
            order['item']['addons'] = {};

            addons.each(function(index) {
                var addon = $(this);
                var select = addon.find('select');
                var checkbox = addon.find('input[type="checkbox"]');
                var item;
                var addonFound = false;
                var text = '';

                if (select.length > 0) {
                    item = select.find('option:selected');
                    addonFound = true;
                    text = item.text();
                } else if (checkbox.length > 0 && checkbox.is(":checked")) {
                    item = checkbox;
                    addonFound = true;
                    text = item.closest('label').text();
                }

                if (addonFound == true) {
                    order['item']['addons'][index] = {};
                    order['item']['addons'][index]['name'] = addon.find('.wc-pao-addon-name').attr('data-addon-name');
                    order['item']['addons'][index]['price_type'] = item.attr('data-price-type');
                    order['item']['addons'][index]['price'] = item.attr('data-price');
                    order['item']['addons'][index]['label'] = item.attr('data-label');
                    order['item']['addons'][index]['value'] = item.val();
                    order['item']['addons'][index]['text'] = text;
                }
            });
        }

        formData.append('order', JSON.stringify(order));
        formData.append('action', 'add_product_item_to_order');
        formData.append('nonce', nonce);
        formData.append('single_product', 1);

        link.addClass('active');

        jQuery.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'post',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response){
                if (response == 1) {
                    html = buildMessageHtml('Product added to order', 'check-circle');

                    populateAndAnimateMessageWindow('.single-product-message-window', html);
                }
                
                link.removeClass('active');
            },
        });
        
    });

    $(document.body).on('added_to_cart', function( event, fragments, cart_hash, button ) {
        var product_id    = button.data('product_id'),   // Get the product id
            product_qty   = button.data('quantity'),     // Get the quantity
            product_sku   = button.data('product_sku'),  // Get the product sku
            product_name  = button.data('product_name'), // Get the product name
            product_price = button.data('product_price'), // Get the product price
            currency      = button.data('currency');     // Get the currency

            html = buildMessageHtml('Product added to order', 'check-circle');

            populateAndAnimateMessageWindow('.page-add-products .product.post-' + product_id + ' .order-approval-message-window', html);
    });

    $('#approval-flow-proceed-to-checkout').on('click', function() {
        var button = $(this);
        var order_id = button.attr('data-order');
        var nonce = button.attr('data-nonce');

        jQuery.ajax({
            type : "post",
            url : wc_add_to_cart_params.ajax_url,
            data : {
                action: "approval_flow_proceed_to_checkout", 
                order_id: order_id, 
                nonce: nonce
            },
            success: function(response) {
                if (response != 0) {
                    window.location.href = response;
                }
            }
        });
    });

    $('#order_review').on('click', '#shipping_method li', function() {
        var item = $(this);
        var radio_button = item.find('input[type="radio"]');
        var list = item.closest('.woocommerce-shipping-methods');
        var chosen_method = list.attr('data-chosen');
        var order_id = list.attr('data-order');
        var clicked_method = radio_button.val();
        var nonce = list.attr('data-nonce');
        var input = $('input[name="chosen_shipping_method"]');

        input.val(clicked_method);

        if (clicked_method != chosen_method) {
            input.val(clicked_method);

            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : wc_add_to_cart_params.ajax_url,
                data : {
                    action: "persist_order_shipping_method", 
                    order_id: order_id, 
                    shipping_method: clicked_method,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('shipping_saved');
                }
            });

        } else {
            input.val('');
        }
    });

    $('body').on('click', '.coupon-order-approval-apply', function() {
        var page = $('.woocommerce-checkout');
        var coupon_form = $('.coupon-order-approval');
        var order_id = $('.woocommerce-checkout-review-order-table').attr('data-order');
        var coupon_code = coupon_form.find('input[name="coupon_code"]').val();
        var nonce = coupon_form.attr('data-nonce');

        if (coupon_code != '') {
            page.block({
                message: null,
                overlayCSS: {
                    background: "#fff",
                    opacity: .4
                }
            });

            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : wc_add_to_cart_params.ajax_url,
                data : {
                    action: "apply_order_review_coupon", 
                    order_id: order_id, 
                    coupon_code: coupon_code,
                    nonce: nonce
                },
                success: function(response) {
                    var message = '';
                    var icon = 'check-circle';
                    
                    showCouponNotice = true;

                    jQuery(document.body).trigger("update_checkout");

                    if (response == 1) {
                        message = 'Coupon applied';
                    } else if (response == 2) {
                        message = 'Coupon already applied';
                    } else if (response == 3) {
                        message = 'Coupon does not exist';
                        icon = 'times-circle';
                    } else if (response == 4) {
                        message = 'Coupon not applicable';
                        icon = 'times-circle';
                    } else {
                        message = 'Error';
                        icon = 'times-circle';
                    }
    
                    $(document.body).bind("updated_checkout", function() {
                        if (showCouponNotice == true) {
                            $('input[name="coupon_code"]').val('');

                            if (message != '') {
                                html = buildMessageHtml(message, icon);
        
                                populateAndAnimateMessageWindow('.coupon .order-approval-message-window', html);
                            }
                        }
                        
                        showCouponNotice = false;
                    });
                }
            }).done(function() {
                page.unblock();
            });
        }
    });

    $('body').on('click', '.woocommerce-remove-coupon', function() {
        var trigger = $(this);
        var coupon_code = trigger.attr('data-coupon');
        var page = $('.woocommerce-checkout');
        var order_id = $('.woocommerce-checkout-review-order-table').attr('data-order');
        var nonce = trigger.attr('data-nonce');

        page.block({
            message: null,
            overlayCSS: {
                background: "#fff",
                opacity: .4
            }
        });

        if (coupon_code != '') {
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : wc_add_to_cart_params.ajax_url,
                data : {
                    action: "remove_order_approval_coupon", 
                    order_id: order_id, 
                    coupon_code: coupon_code,
                    nonce: nonce
                },
                success: function(response) {
                    showCouponNotice = false;

                    jQuery(document.body).trigger("update_checkout");
                    page.unblock();
                }
            });
        }
    });

    $('.modal-exit-approval-flow .confirm').on('click', function() {

    });

    $('#order_review').on('click', '.delete-order-item .trigger', function() {
        let trigger = $(this);
        let item = trigger.closest('.cart_item');
        let itemId = item.attr('data-id');
        let modal = $('.modal-delete-order-item');
        let modalHiddenField = modal.find('input[name="item_to_delete"]');
        let deleteContainer = $('.modal-delete-order-item .order-item-to-delete');
        let name = item.find('.product-name > span').text();
        let quantity = item.find('.product-name .product-quantity-number').text();

        item.addClass('delete-confirm');
        modal.fadeIn().addClass('has-items-to-delete');
        deleteContainer.append('<ul><li>' + name + '(' + quantity + ')</li></ul>');
        modalHiddenField.val(itemId);
    });

    $('.modal-delete-order-item .confirm').on('click', function() {
        let button = $(this);
        let modal = button.closest('.modal-ecard');
        let modalHiddenField = modal.find('input[name="item_to_delete"]');
        let itemToDelete = modalHiddenField.val();
        let nonce = $('.modal-delete-order-item').find('input[name="modal-nonce"]').val();
        let orderId = $('#order_review .shop_table').attr('data-order');
        let page = $('.woocommerce-checkout');

        page.block({
            message: null,
            overlayCSS: {
                background: "#fff",
                opacity: .4
            }
        });

        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : wc_add_to_cart_params.ajax_url,
            data : {
                action: "delete_order_item_in_order_approval_flow", 
                order_id: orderId, 
                item_to_delete: itemToDelete,
                nonce: nonce
            },
            success: function(response) {
                jQuery(document.body).trigger("update_checkout");
                jQuery(document).trigger('order::itemRemovedInApprovalFlow');
                page.unblock();
            }
        });
    });

    $(".approval_button").unbind().click(function (e) {
        e.preventDefault();

        if ($('.check_proof').prop('checked')) {
            let button = $(this);
            let orderToken = $("#order_token").val();
            let currentStatus = $("input[name='current_order_status_number']").val();
            let url = button.attr('ajax-url');
            
            button.text('Please Wait...').css('pointer-events', 'none');

            $.ajax({
                url: url,
                type: "post",
                data: {
                    action: "save_approval_status",
                    current_status: currentStatus,
                    order_status: 'om_12',
                    //order_id: orderId,
                    order_token: orderToken,
                    flag: 'approve'
                },
                success: function (response) {
                    //alert(response);
                    //window.location.href = object_name.templateUrl + '/checkout';
                    window.location.href = response;
                }
            });
        } else {
            alert('Please check to approve proof for production!');
        }
    });

    $('.approval-page .edit-control label').on('click', function(event) {
        let label = $(this);
        let checkbox = label.find('input[type="checkbox"]');
        let textarea = label.closest('.edit-control').find('textarea');

        if (checkbox.is(':checked')) {
            textarea.show();
        } else {
            textarea.hide();
        }
    });

    $(".submit_changes").on('click', function() {
        let button = $(this);
        let orderToken = $("#order_token").val();
        let currentStatus = $("input[name='current_order_status_number']").val();
        let changeRequestText = $(".change_request").val();
        let editControls = $('.edit-controls > div');
        let changeRequestCheckboxes = {};
        let url = button.attr('ajax-url');
        let notesPresent = false;

        editControls.each(function() {
            let controlObj = {};
            let controlDiv = $(this);
            let checkbox = controlDiv.find('input[type="checkbox"');
            let id = checkbox.attr('id');
            let checkedState = checkbox.is(':checked');
            let notes = controlDiv.find('textarea').val();
            let name = controlDiv.find('.edit-controls-big-title').text();

            controlObj['name'] = name;
            controlObj['state'] = checkedState;
            controlObj['notes'] = notes;

            if (notes !== '') {
                notesPresent = true;
            }

            changeRequestCheckboxes[id] = controlObj;
        });

        if (notesPresent) {
            button.text('Please Wait...').css('pointer-events', 'none');

            $.ajax({
                url: url,
                type: "post",
                data: {
                    action: 'save_approval_status',
                    current_status: currentStatus,
                    order_status: 'om_7',
                    order_token: orderToken,
                    change_request: changeRequestText,
                    change_request_checkboxes: changeRequestCheckboxes,
                    flag: 'change'
                },
                success: function (response) {
                    window.location.href = object_name.templateUrl + '/thankyou-page?success=' + response;
                }
            });
        } else {
            alert('Please fill the change request.');
        }
    });

    if ($('body').hasClass('woocommerce-checkout') && $('body').hasClass('order-approval-flow')) {
        let shipToDifferentAddressCheckbox = $('input[name="ship_to_different_address"]');
        let label = shipToDifferentAddressCheckbox.closest('.woocommerce-form__label');
        let checkboxValue = shipToDifferentAddressCheckbox.val();

        if(!shipToDifferentAddressCheckbox.is(':checked') && checkboxValue == 1) {
            label.click();
        }

        if(shipToDifferentAddressCheckbox.is(':checked') && checkboxValue == 0) {
            label.click();
        }

        // Make the shipping fields attribute and property equal in value
        $('.woocommerce-checkout.order-approval-flow .woocommerce-billing-fields .woocommerce-input-wrapper .input-text, .woocommerce-checkout.order-approval-flow .shipping_address .woocommerce-input-wrapper .input-text').each(function() {
            let input = $(this);
            let inputAttributeValue = input.attr('value');
            let inputPropertyValue = input.val();

            if (inputAttributeValue != inputPropertyValue) {
                input.val(inputAttributeValue);
            }
        });

        $('.woocommerce-checkout.order-approval-flow .woocommerce-billing-fields .woocommerce-input-wrapper select, .woocommerce-checkout.order-approval-flow .shipping_address .woocommerce-input-wrapper select').each(function() {
            let select = $(this);
            let options = select.find('option');
            let selectAttributeValue = select.find(":selected").attr('value');
            let selectPropertyValue = select.val();

            if (selectAttributeValue != selectPropertyValue) {
                options.each(function() {
                    let option = $(this);

                    if (option == selectAttributeValue) {
                        option.prop('selected', true);
                    }
                });
            }
        });

        shipToDifferentAddressCheckbox.on('click', function() {
            let checkbox = $(this);
            let value = checkbox.val();

            if (value == '0') {
                checkbox.val('1');
            } else {
                checkbox.val('0');
            }
        });
    }
    

    setLinkBlockingWithinTheFlow();
    animateSingleProductMessage();

    function buildMessageHtml(message, icon) {
        var html = templates.messageBox;

        html = html.replace('{{MESSAGE}}', message);
        html = html.replace('{{ICON}}', icon);

        return html;
    }

    function getAnimationConfig() {
        var config = {};

        config.fadeSpeed = 300;
        config.addedClass = 'product-added';

        return config;
    }

    function animateSingleProductMessage() {
        var message = $('.order-approval-flow.single-product .woocommerce-message');

        setTimeout(function() {
            message.addClass('fade-out');
        }, 2000);

        setTimeout(function() {
            message.removeClass('fade-out');
            message.addClass('faded-out');
        }, 2300);
    }

    function populateAndAnimateMessageWindow(windowSelector, html) {
        var config = getAnimationConfig();
        var messageWindow = $(windowSelector);

        messageWindow.empty().append(html).addClass(config.addedClass).fadeIn(config.fadeSpeed);

        setTimeout(function() {
            messageWindow.fadeOut(config.fadeSpeed * 1.5, function() {
                $('body').click();
                $(this).removeClass(config.addedClass).empty();
            });
        }, 2200);
    }

    function getAllUrlParameters() {
        var url = window.location.href;
        var parameters = {};
        var splitUrl = url.split('?');

        if (splitUrl.length == 2) {
            var splitParams = splitUrl[1].split('&');

            jQuery.each(splitParams, function(index, value){
                var splitParam = splitParams[index].split('=');

                parameters[splitParam[0]] = splitParam[1];
            });
        }
        
        return parameters;
    }

    function getUrlParameterString() {
        var parameters = getAllUrlParameters();
        var keyCount = Object.keys(parameters).length;
        var string = '';

        if (keyCount > 0) {
            string += '?';

            $.each(parameters, function(index, value){
                string += index + '=' + value + '&';
            });

            string = string.substring(0, string.length - 1);
        }

        return string;
    }

    function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    }

    function modifyAddProductsPageElements() {
        var body = $('body');

        if (body.hasClass('page-template-page-add-products')) {
            var addToCartButton = $('.add_to_cart_button');
            var product = $('.page-add-products .product');
            var links = product.find('.thumbnail-wrapper a, .heading-title a');
            var urlParameterString = getUrlParameterString();

            addToCartButton.removeClass('add_to_cart_button ajax_add_to_cart');
            addToCartButton.addClass('add-to-order has-status-animation');
            addToCartButton.attr('href', 'javascript:void(0)');
            
            product.append('<div class="order-approval-message-window"></div>');
    
            links.each(function() {
                var link = $(this);
                var href = link.attr('href');
    
                link.attr('href', href + urlParameterString + '&single-product=1');
            });
        }

        if (body.hasClass('page-template-page-add-products') ||
            (body.hasClass('order-approval-flow') && body.hasClass('single-product'))) { 
            var spinnerHtml = '<i class="fa fa-asterisk icon-spinner"></i>';
            var cartHtml = '<i class="pe-7s-cart mobile-icon"></i>';
            var textHtml = '<span>Add To Order</span>';
            var selectors = '.add-products-listing .add_to_cart_button span, ';
            selectors += ' .order-approval-flow .add-to-order,';
            selectors += ' .single_add_to_cart_button';


            $(selectors)
                .html(spinnerHtml + cartHtml + textHtml);
            
            $('.single_add_to_cart_button')
                .html(spinnerHtml + textHtml)
                .addClass('has-status-animation');
        }
    }

    function setLinkBlockingWithinTheFlow() {
        let blockedLinks;

        blockedLinks += '.order-approval-flow .header-right a,';
        blockedLinks += '.order-approval-flow .header-right .custom-link,';
        blockedLinks += '.order-approval-flow nav.main-menu a,';
        blockedLinks += '.order-approval-flow .logo a,';
        blockedLinks += '.order-approval-flow footer .wpb_column:nth-child(2) .wpb_wrapper a,';
        blockedLinks += '.order-approval-flow footer .wpb_column:nth-child(3) .wpb_wrapper a,';
        blockedLinks += '.order-approval-flow footer .wpb_column:nth-child(4) .wpb_wrapper a,';
        blockedLinks += '.order-approval-flow .breadcrumbs a';

        $(document).on('click', blockedLinks, function(e) {
            e.preventDefault();

            var forwarding_url = $(this).attr('href');
            var modal = $('.modal-ecard.modal-exit-approval-flow');

            modal.fadeIn();

            $('.modal-exit-approval-flow .confirm').off('click.fadeOut');
            $('.modal-exit-approval-flow .confirm').on('click.statusAnimation', function() {
                let nonce = modal.find('input[name="modal-nonce"]').val();
                
                jQuery.ajax({
                    type: "post",
                    dataType: "json",
                    url: wc_add_to_cart_params.ajax_url,
                    data: {
                        action: "exit_the_approval_flow", 
                        forwarding_url: forwarding_url,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data;
                        }
                    }
                });
            });
        });
    }

    function getValidatedCheckoutFormData(form) {
        var formData = form.serializeArray();
        var fieldsToFillOut = [];
        var needsShippingAddressFilled = form.find('[name="ship_to_different_address"]').is(':checked');

        jQuery.each(formData, function(index, field){
            var fieldElement = form.find('[name="' + field.name + '"]');
            var requiredFieldContainer = fieldElement.closest('p.validate-required');
            var isBillingField = field.name.indexOf('billing') != -1;
            var isShippingField = field.name.indexOf('shipping') != -1;

            if (requiredFieldContainer.length > 0 && (field.value == '' || field.value == 'default')) {
                if (isBillingField || (needsShippingAddressFilled && isShippingField)) {
                    fieldsToFillOut.push(field.name);
                }
            }
        });

        formData['fieldsToFillOut'] = fieldsToFillOut;

        return formData;
    }
});

jQuery(function($) {
    $.scroll_to_notices = function(scrollElement) {
      return false;
    };
});
