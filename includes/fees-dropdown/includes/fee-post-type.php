<?php
function fee_post_type() { 
	register_post_type( 'fee',
		array('labels' => array(
                'name' => __('Fees', 'ecard'),
                'singular_name' => __('Fee', 'ecard'),
                'all_items' => __('Fees', 'ecard'),
                'add_new' => __('Add New', 'ecard'),
                'add_new_item' => __('Add New Fee', 'ecard'),
                'edit' => __( 'Edit', 'ecard' ),
                'edit_item' => __('Edit Fees', 'ecard'),
                'new_item' => __('New Fee', 'ecard'),
                'view_item' => __('View Fee', 'ecard'),
                'search_items' => __('Search Fees', 'ecard'),
                'not_found' =>  __('Nothing found in the Database.', 'ecard'),
                'not_found_in_trash' => __('Nothing found in Trash', 'ecard'),
                'parent_item_colon' => ''
			),
			'description' => __( 'This is the example custom fee', 'ecard' ),
			'public' => false,
			'publicly_queryable' => false,
			'exclude_from_search' => true,
			'show_ui' => true,
			'query_var' => true,
			'menu_position' => 8,
			'menu_icon' => 'dashicons-book',
			'rewrite'	=> array( 'slug' => 'fees', 'with_front' => false ),
            'has_archive' => 'fees',
            'show_in_menu' => false, //'edit.php?post_type=product',
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array('title', 'custom-fields')
	 	)
	);	

	//register_taxonomy_for_object_type('fee_cat', 'fee');	
} 

add_action( 'init', 'fee_post_type');