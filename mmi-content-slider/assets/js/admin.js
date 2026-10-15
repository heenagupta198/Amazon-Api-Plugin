(function ($) {
	'use strict';

	var $select = $('#mmi-csl-product-select');

	if (!$select.length || typeof $.fn.select2 === 'undefined') {
		return;
	}

	var $linkedBox = $('#mmi_csl_linked_products');
	if ($linkedBox.length) {
		var $ampBox = $('#side-sortables .postbox').filter(function () {
			return /show amp/i.test($(this).find('.hndle, h2').text());
		}).first();
		if ($ampBox.length) {
			$linkedBox.insertAfter($ampBox);
		}
	}

	var searchDelayMs = 350;
	var lastRequest = null;

	$select.select2({
		width: '100%',
		placeholder: MmiCslAdmin.i18n.placeholder,
		allowClear: true,
		closeOnSelect: false,
		minimumInputLength: 2,
		language: {
			inputTooShort: function () {
				return MmiCslAdmin.i18n.inputTooShort;
			},
			noResults: function () {
				return MmiCslAdmin.i18n.noResults;
			},
			searching: function () {
				return MmiCslAdmin.i18n.searching;
			}
		},
		ajax: {
			url: MmiCslAdmin.ajaxUrl,
			dataType: 'json',
			delay: searchDelayMs,
			cache: true,
			data: function (params) {
				return {
					action: 'mmi_csl_search_products',
					nonce: MmiCslAdmin.nonce,
					term: params.term || ''
				};
			},
			transport: function (params, success, failure) {
				var term = $.trim(params.data.term || '');
				if (term.length < 2 && !/^\d+$/.test(term)) {
					success({ success: true, data: { items: [] } });
					return null;
				}

				if (lastRequest) {
					lastRequest.abort();
				}

				lastRequest = $.ajax(params);
				lastRequest.then(success);
				lastRequest.fail(failure);
				return lastRequest;
			},
			processResults: function (response) {
				if (!response || !response.success || !response.data || !response.data.items) {
					return { results: [] };
				}

				var results = response.data.items.map(function (item) {
					return {
						id: String(item.id),
						text: item.label
					};
				});

				return { results: results };
			}
		}
	});

	$select.on('select2:open', function () {
		document.querySelector('.select2-container--open .select2-search__field').setAttribute(
			'placeholder',
			MmiCslAdmin.i18n.placeholder
		);
	});
})(jQuery);
