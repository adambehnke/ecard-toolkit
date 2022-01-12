<div class="woocommerce-shipping-fields">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>
		<?php 
			if (isset($_GET['order'])) {
				$order_should_ship_to_different_address = order_should_ship_to_different_address($_GET['order']); 
			} else {
				$order_should_ship_to_different_address = false; 
			}
		?>

		<h3 id="ship-to-different-address">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php echo $order_should_ship_to_different_address ? 'checked' : ''; ?> type="checkbox" name="ship_to_different_address" value="<?php echo $order_should_ship_to_different_address ? 1 : 0; ?>" /> <span><?php _e( 'Ship to a different address?', 'woocommerce' ); ?></span>
			</label>
		</h3>

		<h3 class="heading--underlined"><?php _e( 'Shipping Details', 'woocommerce' ); ?></h3>

		<div class="shipping_address">
			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

			<div class="woocommerce-shipping-fields__field-wrapper">
				<?php
					$fields = $checkout->get_checkout_fields('shipping');
                    
                    if (isset($_GET['order']) && is_numeric($_GET['order'])) {
                        $order_id = filter_var($_GET['order'], FILTER_SANITIZE_NUMBER_INT);
                        $order = wc_get_order($order_id);
                    }

					foreach ($fields as $key => $field) {
						if (isset($field['country_field'], $fields[ $field['country_field']])) {
							$field['country'] = $checkout->get_value( $field['country_field']);
						}

                        if (isset($order) && is_a($order, 'WC_Order')) {
                            $method_name = 'get_' . $key;
                            $value = $order->{$method_name}();
                        } else {
                            $value = $checkout->get_value($key);
                        }

						woocommerce_form_field($key, $field, $value);
					}
				?>
			</div>

			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>

		</div>

	<?php endif; ?>
</div>
<div class="woocommerce-additional-fields">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>

		<?php if ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) : ?>

			<h3><?php _e( 'Additional information', 'woocommerce' ); ?></h3>

		<?php endif; ?>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>
