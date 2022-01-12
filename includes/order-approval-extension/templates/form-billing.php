	<div class="woocommerce-billing-fields">
		<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>

			<h3><?php _e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>

		<?php else : ?>

			<h3><?php _e( 'Billing Details', 'woocommerce' ); ?></h3>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

		<div class="woocommerce-billing-fields__field-wrapper">
			<?php
				$fields = $checkout->get_checkout_fields( 'billing' );

                if (isset($_GET['order']) && is_numeric($_GET['order'])) {
                    $order_id = filter_var($_GET['order'], FILTER_SANITIZE_NUMBER_INT);
                    $order = wc_get_order($order_id);
                }

                foreach ($fields as $key => $field) {
                    if (isset( $field['country_field'], $fields[ $field['country_field']])) {
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

		<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
	</div>

	<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
		<div class="woocommerce-account-fields">
			<?php if ( ! $checkout->is_registration_required() ) : ?>

				<p class="form-row form-row-wide create-account">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ) ?> type="checkbox" name="createaccount" value="1" /> <span><?php _e( 'Create an account?', 'woocommerce' ); ?></span>
					</label>
				</p>

			<?php endif; ?>

			<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

			<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

				<div class="create-account">
					<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
						<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
					<?php endforeach; ?>
					<div class="clear"></div>
				</div>

			<?php endif; ?>

			<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
		</div>
	<?php endif; ?>
