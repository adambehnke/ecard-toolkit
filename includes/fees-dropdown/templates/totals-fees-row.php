<tr>
	<td class="label"><?php esc_html_e('Fees:', 'woocommerce'); ?></td>
	<td width="1%"></td>
	<td class="total">
		<?php echo wc_price($fee_data['price'], array('currency' => $order->get_currency())); ?>
	</td>
</tr>