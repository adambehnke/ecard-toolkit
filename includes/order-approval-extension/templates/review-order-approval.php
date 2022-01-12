<?php
    $approval_order_has_discount = false;
	$approval_order_needs_recalculation = true;
	$show_discounts = false;
?>
<table class="shop_table woocommerce-checkout-review-order-table<?php echo $table_class; ?>" data-order="<?php echo esc_attr($order_id); ?>">
	<thead>
		<tr>
			<th class="product-name"><?php _e('Product', 'woocommerce'); ?></th>
			<th class="product-total"><?php _e('Total', 'woocommerce'); ?></th>
			<th></th>
		</tr>
	</thead>
	
		<?php
			do_action('woocommerce_review_order_before_cart_contents');

			if (isset($_POST['post_data'])) {
				save_changed_order_address_parts($_POST['post_data']);
			}

			$shipping_rate = array();
			$chosen_methods = WC()->session->get('chosen_shipping_methods');

			if (is_array($chosen_methods) && !empty($chosen_methods)) {
				$chosen_method = $chosen_methods[0];
				$shipping_for_package = WC()->session->get('shipping_for_package_0');

				if (!is_null($shipping_for_package) && isset($shipping_for_package['rates'])) {
					$shipping_rates = WC()->session->get('shipping_for_package_0')['rates'];
				}

				if (isset($shipping_rates, $shipping_rates[$chosen_method])) {
					$shipping_rate_obj = $shipping_rates[$chosen_method];
				}
			}

			$order = new WC_Order($order_id);
			$quoted_shipping = get_quoted_shipping($order_id);
            $tax_args = get_tax_args_from_post_address();
			$billing_shipping_details_same = are_billing_shipping_details_same($order);			

			if (empty($quoted_shipping) && isset($shipping_rate_obj)) {
				save_chosen_shipping_method_for_order($order, $shipping_rate_obj);
			}

            $order_items = $order->get_items();

			$order_id_attr = '';
			$fees = array();
			$fees_formatted = array();
			$fees_total = 0.00;
            $fees_tax_total = 0.00;
            
            $order_discount = (float)get_post_meta($order_id, '_cart_discount', true);

			if ($order_discount !== '' && (float)$order_discount > 0) {
				$approval_order_has_discount = true;
				$approval_order_needs_recalculation = true;
			}
            
            $fees = $order->get_fees();
			$quoted_shipping = get_quoted_shipping($order_id);

			if (!empty($fees)) {
				foreach ($fees as $fee) {
					$fee_data = $fee->get_data();
					$fees_formatted[] = $fee_data;
					$fees_total += (float)$fee_data['total'];
					$fees_tax_total += (float)$fee_data['total_tax'];
				}

                // Run the fee taxes calculation again to see if the address switched
                // from a non-taxable one, to a taxable one
                if ($fees_tax_total == 0.00) {
		     $order = process_order_taxes($order);
                    //$order->calculate_taxes($tax_args);
                    $order->calculate_totals(false);

                    $fees_inner = $order->get_fees();

                    foreach ($fees_inner as $fee) {
                        $fee_data = $fee->get_data();
                        $fees_tax_total += (float)$fee_data['total_tax'];
                    }
                }

				$approval_order_needs_recalculation = true;
			}

			if (!empty($quoted_shipping)) {
				$shipping_rate['chosen'] = (float)$quoted_shipping['cost'];
				$approval_order_needs_recalculation = true;
			} else {
				if (isset($shipping_rate_obj) && !is_null($shipping_rate_obj)) {
					$shipping_rate['chosen'] = $shipping_rate_obj->get_cost();
				}
			}

			if (isset($shipping_rate, $shipping_rate['chosen'])) {
				$order->set_shipping_total($shipping_rate['chosen']);
			}

			$order_id_attr = 'data-order="' . esc_attr($order_id) .'"';

			if ($approval_order_needs_recalculation) {
				$subtotal = $order->get_subtotal();
				$discounted_subtotal_tax = 0.00;
				$total_taxes = 0.00;
				$tax_rate = 0;
				$tax_rate_label = '';

				$taxes = $order->get_taxes();
				$cart_taxes_total = WC()->cart->get_taxes();

				$order = process_order_taxes($order);
				$order->calculate_totals(false);

				$tax_totals = $order->get_tax_totals();

				foreach ($tax_totals as $tax_total) {
					$total_taxes += $tax_total->amount;
				}
    
                save_total_taxes_to_wp_orders($order_id, $total_taxes);

				$old_subtotal = (float)$subtotal;

				if ($approval_order_has_discount) {
					$subtotal -= $order_discount;
				}

				$new_total = 0.00;
				$new_total += $old_subtotal; 
				$new_total -= $order_discount;

				if (isset($shipping_rate, $shipping_rate['chosen'])) {
					$new_total += $shipping_rate['chosen'];
				}

				$new_total += $total_taxes;
				$new_total += $fees_total;

                $order->set_total($new_total);
                $order->save();
			}

			update_cart_with_order_items($order);

	?>
	<tbody class="order-data">
	<?php
			foreach ($order_items as $item_key => $item) {
                $item_subtotal = $item->get_subtotal();
				$item_quantity = $item->get_quantity();
				$_product   = $item->get_product();
				
				if ($existing_order) {
					$order_item_id_attr  = ' data-id="' . esc_attr($item->get_id()) . '"';
					$order_item_id_attr .= ' data-key="' . esc_attr($item_key) . '"';
					$order_item_id_attr .= ' data-product-id="' . esc_attr($_product->get_id()) . '"';
				} else {
					$order_item_id_attr = '';
				}
			?>
					<tr class="<?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $item, $item_key)); ?>"<?php echo $order_item_id_attr; ?>>
						<td class="product-name">
							<span><?php echo apply_filters('woocommerce_cart_item_name', $_product->get_name(), $item, $item_key) . '&nbsp;'; ?></span>
							<?php 								
								$edit_html  = ' <strong class="product-quantity hidden-active hidden-saving">&times; <span class="product-quantity-number">' . esc_html($item['quantity']) . '</span></strong>';
								$edit_html .= get_order_item_edit_html($_product->get_id(), $item);

								echo apply_filters('woocommerce_checkout_cart_item_quantity', $edit_html, $item, $item_key);
							?>
						</td>
						<td class="product-total">
							<?php if (isset($item_subtotal)): ?>
								<?php echo wc_price($item_subtotal); ?>
                            <?php endif; ?>
						</td>
						<td class="delete-order-item">
							<span class="trigger"></span>
						</td>
					</tr>
			<?php
			}

			do_action('woocommerce_review_order_after_cart_contents');
		?>

        <tr class="order-review-quantity-controls-row">
			<td colspan="2">
				<div class="order-review-quantity-controls" <?php echo $order_id_attr; ?>>
					<?php
						$control_html = get_order_review_controls_html($order_id);

						echo $control_html;
					?>
				</div>
			</td>
		</tr>
	</tbody>
	<tfoot>

		<?php if ($show_discounts && $approval_order_has_discount): ?>
		<tr class="cart-discount">
			<th><?php _e('Discount', 'woocommerce'); ?></th>
			<td>-<?php echo wc_price($order_discount); ?></td>
		</tr>
		<?php endif; ?>

		<?php foreach ($order->get_coupons() as $code => $coupon) : ?>
			<tr class="cart-discount order-coupon coupon-<?php echo esc_attr(sanitize_title($code)); ?>">
				<th><?php wc_cart_totals_coupon_label($coupon->get_code()); ?></th>
				<td><?php wc_order_totals_coupon_html($coupon->get_code()); ?></td>
			</tr>
		<?php endforeach; ?>

        <tr class="cart-subtotal">
            <th><?php _e('Subtotal', 'woocommerce'); ?></th>
            <td><?php echo wc_price($subtotal); ?></td>
        </tr>

		<?php if (!empty($quoted_shipping)) : ?>
			<tr class="shipping">
				<th><?php _e('Shipping', 'ecard'); ?></th>
				<td><?php echo wc_price($quoted_shipping['cost']); ?></td>
			</tr>
		<?php else: ?>

			<?php do_action('woocommerce_review_order_before_shipping'); ?>

			<?php process_and_display_order_review_shipping($order_id); ?>

			<input type="hidden" name="chosen_shipping_method" value="" />

			<?php do_action('woocommerce_review_order_after_shipping'); ?>

		<?php endif; ?>

		<?php if ($order_id > 0): ?>
			<?php foreach ($fees_formatted as $fee) : ?>
				<tr class="fee">
					<th><?php echo esc_html($fee['name']); ?></th>
					<td><?php echo wc_price($fee['total']); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<?php foreach (WC()->cart->get_fees() as $fee) : ?>
				<tr class="fee">
					<th><?php echo esc_html($fee->name); ?></th>
					<td><?php wc_cart_totals_fee_html($fee); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		
		<?php if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax()) : ?>
			<?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
				<?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
					<tr class="tax-rate tax-rate-<?php echo sanitize_title($code); ?>">
						<th><?php echo esc_html($tax->label); ?></th>
						<?php if (!empty($fees)): ?>
						<td><?php echo wc_price($total_taxes); ?></td>
						<?php else: ?>
						<td><?php echo wp_kses_post($tax->formatted_amount); ?></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html(WC()->countries->tax_or_vat()); ?></th>
					<td><?php echo wc_price($total_taxes); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action('woocommerce_review_order_before_order_total'); ?>

		<tr class="order-total">
			<th><?php _e('Total', 'woocommerce'); ?></th>
			<td><?php echo wc_price($new_total); ?></td>
		</tr>

		<?php do_action('woocommerce_review_order_after_order_total'); ?>

	</tfoot>
</table>
