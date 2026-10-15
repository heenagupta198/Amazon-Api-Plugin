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

	$linkedBox.on('click', '.mmi-csl-tab', function () {
		var tab = $(this).data('tab');
		$linkedBox.find('.mmi-csl-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$linkedBox.find('.mmi-csl-panel').each(function () {
			var $panel = $(this);
			var active = $panel.data('panel') === tab;
			$panel.toggleClass('is-active', active);
			$panel.prop('hidden', !active);
		});
	});

	var searchDelayMs = 280;
	var lastRequest = null;

	function canSearch(term) {
		term = $.trim(term || '');
		return term.length >= 2 || /^\d+$/.test(term);
	}

	function addOptions(items) {
		if (!items || !items.length) {
			return;
		}
		items.forEach(function (item) {
			var id = String(item.id);
			if ($select.find('option[value="' + id + '"]').length) {
				return;
			}
			var option = new Option(item.label, id, true, true);
			$select.append(option);
		});
		$select.trigger('change');
		initSortable();
	}

	function initSortable() {
		var $rendered = $select.next('.select2-container').find('ul.select2-selection__rendered');
		if (!$rendered.length || $rendered.data('mmi-sortable')) {
			return;
		}

		$rendered.data('mmi-sortable', true);
		$rendered.sortable({
			items: 'li.select2-selection__choice',
			cursor: 'move',
			tolerance: 'pointer',
			stop: function () {
				$rendered.find('li.select2-selection__choice').each(function () {
					var title = $(this).attr('title');
					var $option = $select.find('option').filter(function () {
						return $(this).text() === title;
					});
					if ($option.length) {
						$select.append($option);
					}
				});
			}
		});
	}

	$select.select2({
		width: '100%',
		placeholder: MmiCslAdmin.i18n.placeholder,
		allowClear: false,
		closeOnSelect: false,
		minimumInputLength: 0,
		dropdownParent: $linkedBox,
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
			type: 'POST',
			dataType: 'json',
			delay: searchDelayMs,
			cache: false,
			data: function (params) {
				return {
					action: 'mmi_csl_search_products',
					nonce: MmiCslAdmin.nonce,
					term: params.term || ''
				};
			},
			transport: function (params, success, failure) {
				var term = $.trim(params.data.term || '');
				if (!canSearch(term)) {
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

				return {
					results: response.data.items.map(function (item) {
						return {
							id: String(item.id),
							text: item.label
						};
					})
				};
			}
		}
	});

	$select.on('select2:select', function () {
		window.setTimeout(initSortable, 0);
	});

	$select.on('select2:open', function () {
		var field = document.querySelector('.select2-container--open .select2-search__field');
		if (field) {
			field.setAttribute('placeholder', MmiCslAdmin.i18n.placeholder);
		}
	});

	initSortable();

	$('#mmi-csl-bulk-add').on('click', function () {
		var raw = $('#mmi-csl-bulk-ids').val();
		if (!raw) {
			return;
		}

		$.post(MmiCslAdmin.ajaxUrl, {
			action: 'mmi_csl_resolve_products',
			nonce: MmiCslAdmin.nonce,
			ids: raw
		})
			.done(function (response) {
				if (!response || !response.success) {
					return;
				}
				addOptions(response.data.items || []);
				$('#mmi-csl-bulk-ids').val('');
				$linkedBox.find('.mmi-csl-tab[data-tab="search"]').trigger('click');
			});
	});

	$('#mmi-csl-clear-all').on('click', function () {
		$select.val(null).trigger('change');
	});
})(jQuery);
