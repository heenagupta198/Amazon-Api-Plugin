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

		if (data.availability_label) {
			$scope.find('.mmi-aps-display-status')
				.removeClass('mmi-aps-display-status--available mmi-aps-display-status--unavailable')
				.addClass('mmi-aps-display-status--' + (data.availability || 'available'))
				.text(data.availability_label);
		}
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

	function setFlipkartMessage($scope, text, type) {
		$scope.find('.mmi-aps-flipkart-fetch-message')
			.removeClass('mmi-aps-fetch-message--success mmi-aps-fetch-message--error')
			.addClass(type ? 'mmi-aps-fetch-message--' + type : '')
			.text(text || '');
	}

	$(document).on('click', '.mmi-aps-fetch-flipkart-price', function () {
		var $btn = $(this);
		var $scope = $btn.closest('.options_group');
		var productId = $btn.data('product-id');
		var url = ($('#mmi_flipkart_product_url').val() || '').trim();
		var $spinner = $btn.siblings('.mmi-aps-flipkart-fetch-spinner');

		if (!url) {
			setFlipkartMessage($scope, mmiApsAdmin.i18n.noFlipkartUrl || 'Please enter a Flipkart product URL first.', 'error');
			return;
		}

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		setFlipkartMessage($scope, mmiApsAdmin.i18n.fetchingFlipkart || 'Fetching from Flipkart…', '');

		$.post(mmiApsAdmin.ajaxUrl, {
			action: 'mmi_aps_fetch_flipkart_price',
			nonce: mmiApsAdmin.nonce,
			product_id: productId,
			flipkart_url: url
		})
			.done(function (response) {
				if (response.success) {
					$scope.find('.mmi-aps-flipkart-display-price').text(response.data.price_formatted || '—');
					$scope.find('.mmi-aps-flipkart-display-original-price').text(response.data.original_formatted || '—');
					$scope.find('.mmi-aps-flipkart-display-last-updated').text(response.data.last_updated || '—');
					$scope.find('.mmi-aps-flipkart-display-status')
						.removeClass('mmi-aps-flipkart-display-status--in_stock mmi-aps-flipkart-display-status--out_of_stock mmi-aps-flipkart-display-status--unavailable')
						.addClass('mmi-aps-flipkart-display-status--' + (response.data.availability || 'unavailable'))
						.text(response.data.availability_label || '');
					setFlipkartMessage($scope, response.data.message, 'success');
				} else {
					setFlipkartMessage($scope, (response.data && response.data.message) || mmiApsAdmin.i18n.error, 'error');
				}
			})
			.fail(function () {
				setFlipkartMessage($scope, mmiApsAdmin.i18n.error, 'error');
			})
			.always(function () {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
			});
	});

	$(document).on('click', '.mmi-aps-fetch-all-variants', function () {
		var $btn = $(this);
		var productId = $btn.data('product-id');
		var $spinner = $btn.siblings('.mmi-aps-fetch-all-spinner');
		var $msg = $btn.siblings('.mmi-aps-fetch-all-message');

		$btn.prop('disabled', true);
		$spinner.addClass('is-active');
		$msg.text(mmiApsAdmin.i18n.fetching).css('color', '');

		$.post(mmiApsAdmin.ajaxUrl, {
			action: 'mmi_aps_fetch_all_variations',
			nonce: mmiApsAdmin.nonce,
			product_id: productId
		})
			.done(function (response) {
				if (response.success) {
					$msg.text(response.data.message || mmiApsAdmin.i18n.success).css('color', '#007017');
					window.location.reload();
				} else {
					$msg.text((response.data && response.data.message) || mmiApsAdmin.i18n.error).css('color', '#b32d2e');
				}
			})
			.fail(function () {
				$msg.text(mmiApsAdmin.i18n.error).css('color', '#b32d2e');
			})
			.always(function () {
				$btn.prop('disabled', false);
				$spinner.removeClass('is-active');
			});
	});

	$(document).on('click', '.mmi-aps-var-fetch-amazon', function () {
		var $btn = $(this);
		var $wrap = $btn.closest('.mmi-aps-variation-marketplace');
		var variationId = $btn.data('variation-id');
		var asin = ($wrap.find('.mmi-aps-var-asin').val() || '').trim().toUpperCase();
		var $msg = $wrap.find('.mmi-aps-var-fetch-msg');

		if (!asin) {
			$msg.text(mmiApsAdmin.i18n.noAsin).css('color', '#b32d2e');
			return;
		}

		$btn.prop('disabled', true);
		$msg.text(mmiApsAdmin.i18n.fetching).css('color', '');

		$.post(mmiApsAdmin.ajaxUrl, {
			action: 'mmi_aps_fetch_price',
			nonce: mmiApsAdmin.nonce,
			product_id: variationId,
			asin: asin
		})
			.done(function (response) {
				if (response.success) {
					$msg.text(response.data.message || mmiApsAdmin.i18n.success).css('color', '#007017');
				} else {
					$msg.text((response.data && response.data.message) || mmiApsAdmin.i18n.error).css('color', '#b32d2e');
				}
			})
			.fail(function () {
				$msg.text(mmiApsAdmin.i18n.error).css('color', '#b32d2e');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});

	$(document).on('click', '.mmi-aps-var-fetch-flipkart', function () {
		var $btn = $(this);
		var $wrap = $btn.closest('.mmi-aps-variation-marketplace');
		var variationId = $btn.data('variation-id');
		var url = ($wrap.find('.mmi-aps-var-flipkart-url').val() || '').trim();
		var $msg = $wrap.find('.mmi-aps-var-fetch-msg');

		if (!url) {
			$msg.text(mmiApsAdmin.i18n.noFlipkartUrl).css('color', '#b32d2e');
			return;
		}

		$btn.prop('disabled', true);
		$msg.text(mmiApsAdmin.i18n.fetchingFlipkart).css('color', '');

		$.post(mmiApsAdmin.ajaxUrl, {
			action: 'mmi_aps_fetch_flipkart_price',
			nonce: mmiApsAdmin.nonce,
			product_id: variationId,
			flipkart_url: url
		})
			.done(function (response) {
				if (response.success) {
					$msg.text(response.data.message).css('color', '#007017');
				} else {
					$msg.text((response.data && response.data.message) || mmiApsAdmin.i18n.error).css('color', '#b32d2e');
				}
			})
			.fail(function () {
				$msg.text(mmiApsAdmin.i18n.error).css('color', '#b32d2e');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});
})(jQuery);
