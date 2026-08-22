(function ($) {
	'use strict';

	function setMessage($scope, text, type) {
		$scope.find('.mmi-aps-fetch-message')
			.removeClass('mmi-aps-fetch-message--success mmi-aps-fetch-message--error')
			.addClass(type ? 'mmi-aps-fetch-message--' + type : '')
			.text(text || '');
	}

	function updateDisplay($scope, data) {
		$scope.find('.mmi-aps-display-price').text(data.price_formatted || '—');
		$scope.find('.mmi-aps-display-original-price').text(data.original_formatted || '—');
		$scope.find('.mmi-aps-display-title').text(data.title || '—');
		$scope.find('.mmi-aps-display-delivery').text(data.delivery || '—');
		$scope.find('.mmi-aps-display-last-updated').text(data.last_updated || '—');
	}

	function syncAsinInputs(asin) {
		if (!asin) {
			return;
		}

		$('.mmi-aps-asin-input, #mmi_amazon_asin').val(asin);
	}

	$(document).on('click', '.mmi-aps-fetch-price', function () {
		var $btn = $(this);
		var $scope = $btn.closest('.mmi-aps-synced-data').length
			? $btn.closest('.options_group, #mmi-aps-product-box .inside, .postbox')
			: $btn.closest('#mmi_amazon_price_product_data, #mmi-aps-product-box');

		if (!$scope.length) {
			$scope = $btn.parent();
		}

		var asinInputId = $btn.data('asin-input') || 'mmi_amazon_asin';
		var asin = (
			$('#' + asinInputId).val() ||
			$('#mmi_amazon_asin-box').val() ||
			$('#mmi_amazon_asin').val() ||
			''
		).trim().toUpperCase();
		var productId = $btn.data('product-id');
		var $spinner = $btn.siblings('.mmi-aps-fetch-spinner');

		if (!asin) {
			setMessage($scope, mmiApsAdmin.i18n.noAsin, 'error');
			return;
		}

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		setMessage($scope, mmiApsAdmin.i18n.fetching, '');

		$.post(mmiApsAdmin.ajaxUrl, {
			action: 'mmi_aps_fetch_price',
			nonce: mmiApsAdmin.nonce,
			product_id: productId,
			asin: asin
		})
			.done(function (response) {
				if (response.success) {
					syncAsinInputs(response.data.asin || asin);
					$('.mmi-aps-synced-data').each(function () {
						updateDisplay($(this).closest('.options_group, #mmi-aps-product-box .inside, .postbox, #mmi_amazon_price_product_data'), response.data);
					});
					updateDisplay($scope, response.data);
					setMessage($scope, response.data.message || mmiApsAdmin.i18n.success, 'success');
				} else {
					setMessage($scope, (response.data && response.data.message) || mmiApsAdmin.i18n.error, 'error');
				}
			})
			.fail(function () {
				setMessage($scope, mmiApsAdmin.i18n.error, 'error');
			})
			.always(function () {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
			});
	});
})(jQuery);
