jQuery(document).ready(function($) {
    init();

    $('body').on('click', '.save-orders-list', function() {
        var saveButton = $(this);
        var selectsToSave = $('select.enqueue-for-save.enqueued');
        var orderStatuses = {
            'order_payment_statuses': {}, 
            'order_commission_statuses': {}, 
            action: 'update_orders_from_list'
        };

        selectsToSave.each(function() {
            var select = $(this);
            var parentRow = select.closest('tr');
            var postId = parentRow.attr('id');
            var id = postId.replace('post-','');
            var status = select.find('option:selected').val();
            var name = select.attr('name');

            if (name == 'order_payment_status') {
                orderStatuses['order_payment_statuses'][id] = status;
            } else if (name == 'order_commission_status') {
                orderStatuses['order_commission_statuses'][id] = status;
            }
        });

        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            data: orderStatuses,
            beforeSend: function() {
                saveButton.addClass('toggled');
                saveButton.find('.button-text').text('Saving...');
            },
            success: function (response) {
                saveButton.removeClass('toggled');
                saveButton.find('.button-text').text('Saved');
            },
            complete: function() {
                location.reload();
                setTimeout(function() {
                    location.reload();
                }, 500);
            }
        });
    });

    $('body').on('click', '.toggle-screen-options', function() {
        var button = $(this);
        var hiddenButton = $('#show-settings-link');

        button.toggleClass('toggled');
        hiddenButton.click();
    });

    $('select.enqueue-for-save').on('change', function() {
        var select = $(this);
        var selected = select.find('option:selected');
        var currentValue = select.attr('data-current');
        var value = selected.val();
        var saveButton = $('.save-button');
        var selectedNumber = 0;

        
        if (currentValue == value) {
            select.removeClass('enqueued');
        } else {
            select.addClass('enqueued');
        }

        selectedNumber = $('select.enqueue-for-save.enqueued').length;

        if (selectedNumber > 0) {
            saveButton.addClass('active');
        } else {
            saveButton.removeClass('active');
        }
    });

    function init() {
        appendOrdersButtons();
        makeTheOrderRowsUnclickable();
    }

    function appendOrdersButtons() {
        var html = '';

        html += '<button type="button" role="tab" aria-selected="false" aria-controls="activity-panel-save" id="activity-panel-tab-save" class="save-orders-list save-button stateful-button components-button woocommerce-layout__activity-panel-tab">';
        html += '<i class="material-icons-outlined state-1">done_outline</i>';
        html += '<i class="material-icons-outlined state-2 rotate">autorenew</i>';
        html += '<span class="button-text">Save</span></button>';

        html += '<button type="button" role="tab" aria-selected="false" aria-controls="activity-panel-save" id="activity-panel-tab-options" class="toggle-screen-options components-button woocommerce-layout__activity-panel-tab">';
        html += '<i class="material-icons-outlined">settings</i>';
        html += '<span class="button-text">Options</span></button>';

        $('.woocommerce-layout__activity-panel .woocommerce-layout__activity-panel-tabs').prepend(html);
    }

    function makeTheOrderRowsUnclickable() {
        $('.woocommerce-page.post-type-shop_order .wp-list-table tr').addClass('no-link');
    }
});