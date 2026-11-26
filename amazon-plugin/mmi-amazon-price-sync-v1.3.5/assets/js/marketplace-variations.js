(function ($) {
	'use strict';

	function updateRow($block, platform, data) {
		var $row = $block.find('.mmi-marketplace-pricing__row--' + platform);
		if (!$row.length || !data) {
			return;
		}

		$row.find('.mmi-marketplace-pricing__availability')
			.removeClass('mmi-marketplace-pricing__availability--in_stock mmi-marketplace-pricing__availability--available mmi-marketplace-pricing__availability--out_of_stock mmi-marketplace-pricing__availability--unavailable')
			.addClass('mmi-marketplace-pricing__availability--' + (data.availability || 'unavailable'))
			.text(data.availability_label || '');

		var $priceArea = $row.find('.mmi-marketplace-pricing__price-area');
		var buyUrl = data.buy_url || '';
		var showPrice = data.show_price && data.price;

		$row.find('.mmi-marketplace-pricing__price, .mmi-marketplace-pricing__stale, .mmi-marketplace-pricing__na').remove();

		if (showPrice) {
			var $price = $('<a class="mmi-marketplace-pricing__price" target="_blank" rel="nofollow sponsored noopener"></a>');
			$price.attr('href', buyUrl);
			$price.html(data.price_html || '');
			$priceArea.prepend($price);
		} else if (data.configured) {
			$priceArea.prepend('<span class="mmi-marketplace-pricing__stale">' + (window.mmiApsMarketplace && mmiApsMarketplace.staleText ? mmiApsMarketplace.staleText : 'Price update in progress') + '</span>');
		} else {
			$priceArea.prepend('<span class="mmi-marketplace-pricing__na">—</span>');
		}

		var $buy = $row.find('.mmi-marketplace-pricing__buy');
		if (buyUrl) {
			if (!$buy.length) {
				$buy = $('<a class="mmi-marketplace-pricing__buy button alt" target="_blank" rel="nofollow sponsored noopener"></a>');
				$priceArea.append($buy);
			}
			$buy.attr('href', buyUrl).text(data.buy_text || 'Buy Now').show();
		} else if ($buy.length) {
			$buy.hide();
		}
	}

	function onVariationFound(variation) {
		var $block = $('.mmi-marketplace-pricing[data-variable="1"]');
		if (!$block.length || !variation || !variation.mmi_marketplace) {
			return;
		}

		var platforms = variation.mmi_marketplace.platforms || {};
		updateRow($block, 'amazon', platforms.amazon);
		updateRow($block, 'flipkart', platforms.flipkart);

		if (variation.mmi_sale_status && variation.mmi_sale_status.label) {
			$block.find('.mmi-marketplace-pricing__status-value')
				.removeClass(function (i, c) {
					return (c.match(/mmi-marketplace-pricing__status-value--\S+/g) || []).join(' ');
				})
				.addClass('mmi-marketplace-pricing__status-value--' + (variation.mmi_sale_status.slug || ''))
				.text(variation.mmi_sale_status.label);
		}
	}

	$(function () {
		var $form = $('form.variations_form');
		if (!$form.length) {
			return;
		}

		$form.on('found_variation', function (event, variation) {
			onVariationFound(variation);
		});

		$form.on('reset_data', function () {
			var $block = $('.mmi-marketplace-pricing[data-variable="1"]');
			$block.find('.mmi-marketplace-pricing__availability').text('');
		});
	});
}(jQuery));