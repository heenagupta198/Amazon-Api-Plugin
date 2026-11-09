<?php
/**
 * Grouped TV specification table (same markup as mobile specs-wrapper).
 *
 * @var WC_Product $product
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $product ) || ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$grouped = MMI_TV_Frontend::grouped_attributes( $product );
if ( ! $grouped ) {
	return;
}
?>
<div class="specs-wrapper mmi-tv-specs">
	<?php foreach ( $grouped as $group => $rows ) : ?>
		<h3 class="spec-title"><?php echo esc_html( $group ); ?></h3>
		<table class="spec-table">
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td class="spec-label"><?php echo esc_html( $row['label'] ); ?></td>
					<td class="spec-value"><?php echo esc_html( $row['value'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endforeach; ?>
</div>
