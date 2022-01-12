var orderAddress = {}; 

orderAddress.getOrderTaxableAddress = function() {
    var address = {};

    if (typeof woocommerce_admin_meta_boxes != 'undefined') {
        if ('shipping' === woocommerce_admin_meta_boxes.tax_based_on) {
            address = this.get('shipping');
        }
    
        if ('billing' === woocommerce_admin_meta_boxes.tax_based_on || 
            address['address_1'] == '' || !address['country'] || address['country'] == '' || 
            address['state'] == '' || address['postcode'] == '' || address['city'] == '') {
            address = this.get('billing');
        }
    } else if (jQuery('body').hasClass('admin_page_Snap-Shot-Order')) {
        address = this.get('shipping');
    }

    return address;
};

orderAddress.get = function(type) {
    let address = {'allFieldsHaveInfo': false};
    let fieldsWithInfo = 0;
    let fields = ['address_1', 'country', 'state', 'postcode', 'city'];

    jQuery.each(fields, function(index, field) {
        let inputValue = jQuery('#_' + type + '_' + field).val();

        address[field] = inputValue;

        if (inputValue != '') {
            fieldsWithInfo++;
        }
    });

    if (fieldsWithInfo == fields.length) {
        address['allFieldsHaveInfo'] = true;
    }

    return address;
}

orderAddress.getAll = function() {
    var addresses = {
        billing: this.get('billing'),
        shipping: this.get('shipping')
    }

    return addresses;
}

orderAddress.isTaxableAddressComplete = function() {
    var address = this.getOrderTaxableAddress();

    if (address['address_1'] == '' || 
        address['country'] == '' || 
        address['state'] == '' || 
        address['postcode'] == '' || 
        address['city'] == '') {
            return false;
    }

    return true;
}
