(function ($) {
	'use strict';

	function updateAffiliateFromVariation(variation) {
		if (!variation || !variation.mmi_aps) {
			return;
		}

		var data = variation.mmi_aps;
		var $links = $('.mmi-aps-affiliate-btn, .mmi-91-teaser__see-prices');
		var label = data.buy_label || 'Go To Store';

		if (data.in_stock && data.affiliate_url) {
			$('.mmi-aps-affiliate-btn').each(function () {
				var $btn = $(this);
				$btn.attr('href', data.affiliate_url).text(label);
				$btn.closest('.mmi-aps-affiliate-wrap').show();
			});
		} else {
			$('.mmi-aps-affiliate-btn').closest('.mmi-aps-affiliate-wrap').hide();
		}
	}

	$(function () {
		var $form = $('form.variations_form');
		if (!$form.length) {
			return;
		}

		$form.on('found_variation', function (event, variation) {
			updateAffiliateFromVariation(variation);
		});

		$form.on('reset_data', function () {
			$('.mmi-aps-affiliate-btn').closest('.mmi-aps-affiliate-wrap').hide();
		});
	});
}(jQuery));