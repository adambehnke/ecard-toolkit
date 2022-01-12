<?php if ( wc_coupons_enabled() ) { ?>
	<div class="coupon coupon-order-approval" data-nonce="<?php echo wp_create_nonce("apply_order_review_coupon_nonce"); ?>">
		<div class="coupon-inner">
			<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_html_e( 'Coupon code', 'boxshop' ); ?>" /> 
			<span class="coupon-order-approval-apply">Apply Coupon</span>
		</div>
		<div class="order-approval-message-window"></div>

		<?php do_action( 'woocommerce_cart_coupon' ); ?>
	</div>
<?php } ?>