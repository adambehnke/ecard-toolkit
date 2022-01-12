<?php
if(function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
        'key' => 'fee_data',
        'title' => 'Fee data',
        'fields' => array (
            array (
                'key' => 'field_1',
                'label' => 'Amount',
                'name' => 'fee_amount',
                'type' => 'text',
                'placeholder' => '0'
            ),
            array (
                'key' => 'field_2',
                'label' => 'Fee Type',
                'name' => 'fee_type',
                'type' => 'select',
                'choices' => array(
                    'flat_fee' => 'Flat Fee',
                    'percentage_based' => 'Percentage Based'
                )
            )
        ),
        'location' => array (
            array (
                array (
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'fee',
                ),
            ),
        ),
    ));
}