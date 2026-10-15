(function ($) {
	'use strict';

	var searchTimer = null;
	var $search = $('#mmi-csl-product-search');
	var $results = $('#mmi-csl-product-results');
	var $selected = $('#mmi-csl-product-selected');

	if (!$search.length) {
		return;
	}

	// Place metabox directly under the AMP box when present.
	var $linkedBox = $('#mmi_csl_linked_products');
	if ($linkedBox.length) {
		var $ampBox = $('#side-sortables .postbox').filter(function () {
			return /show amp/i.test($(this).find('.hndle, h2').text());
		}).first();
		if ($ampBox.length) {
			$linkedBox.insertAfter($ampBox);
		}
	}

	function selectedIds() {
		var ids = [];
		$selected.find('input[name="mmi_csl_product_ids[]"]').each(function () {
			ids.push(String($(this).val()));
		});
		return ids;
	}

	function addProduct(item) {
		if (selectedIds().indexOf(String(item.id)) !== -1) {
			return;
		}

		var $li = $('<li></li>').attr('data-id', item.id);
		$li.append($('<span class="mmi-csl-selected-label"></span>').text(item.label));
		$li.append(
			$('<button type="button" class="button-link mmi-csl-remove" aria-label="Remove">&times;</button>')
		);
		$li.append(
			$('<input type="hidden" name="mmi_csl_product_ids[]">').val(item.id)
		);
		$selected.append($li);
	}

	function renderResults(items) {
		$results.empty();
		if (!items || !items.length) {
			$results.append('<li class="mmi-csl-empty">No products found.</li>');
			return;
		}

		items.forEach(function (item) {
			var $btn = $('<button type="button" class="button-link mmi-csl-add"></button>').text(item.label);
			$btn.on('click', function () {
				addProduct(item);
				$results.empty();
				$search.val('').trigger('focus');
			});
			$results.append($('<li></li>').append($btn));
		});
	}

	function runSearch() {
		var term = $.trim($search.val());
		if (term.length < 2 && !/^\d+$/.test(term)) {
			$results.empty();
			return;
		}

		$.getJSON(MmiCslAdmin.ajaxUrl, {
			action: 'mmi_csl_search_products',
			nonce: MmiCslAdmin.nonce,
			term: term
		})
			.done(function (response) {
				if (!response || !response.success) {
					renderResults([]);
					return;
				}
				renderResults(response.data.items || []);
			})
			.fail(function () {
				renderResults([]);
			});
	}

	$search.on('input', function () {
		window.clearTimeout(searchTimer);
		searchTimer = window.setTimeout(runSearch, 280);
	});

	$selected.on('click', '.mmi-csl-remove', function () {
		$(this).closest('li').remove();
	});
})(jQuery);
