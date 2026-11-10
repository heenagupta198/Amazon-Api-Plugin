(function ($) {
	'use strict';

	var debounceTimer = null;
	var $box = $('.mmi-ra-metabox');
	if (!$box.length) {
		return;
	}

	var postId = parseInt($box.data('post-id'), 10) || 0;
	var $search = $('#mmi-ra-search');
	var $results = $('#mmi-ra-results');
	var $selected = $('#mmi-ra-selected');
	var $hidden = $('#mmi-ra-related-ids');
	var $addBtn = $('#mmi-ra-add-selected');
	var $removeBtn = $('#mmi-ra-remove-selected');
	var $emptyMsg = $('.mmi-ra-selected-empty');

	function getPriority() {
		return $('input[name="mmi_ra_priority"]:checked').val() || '7';
	}

	function selectedIds() {
		var ids = [];
		$selected.find('.mmi-ra-selected-item').each(function () {
			ids.push(parseInt($(this).data('id'), 10));
		});
		return ids;
	}

	function syncHidden() {
		$hidden.val(selectedIds().join(','));
		$emptyMsg.toggleClass('hidden', selectedIds().length > 0);
		updateButtons();
	}

	function updateButtons() {
		var checkedResults = $results.find('.mmi-ra-result-cb:checked').length;
		$addBtn.prop('disabled', checkedResults === 0);

		var checkedSelected = $selected.find('.mmi-ra-selected-remove-cb:checked').length;
		$removeBtn.prop('disabled', checkedSelected === 0);
	}

	function escapeHtml(text) {
		return $('<div>').text(text).html();
	}

	function resultRow(item) {
		var id = item.id;
		var disabled = selectedIds().indexOf(id) !== -1 ? ' disabled checked' : '';
		return (
			'<label class="mmi-ra-result-row' +
			(disabled ? ' is-added' : '') +
			'">' +
			'<input type="checkbox" class="mmi-ra-result-cb" value="' +
			id +
			'"' +
			disabled +
			' />' +
			'<img src="' +
			escapeHtml(item.thumb) +
			'" alt="" width="36" height="36" loading="lazy" />' +
			'<span class="mmi-ra-result-text"><strong>' +
			escapeHtml(item.title) +
			'</strong><small>' +
			escapeHtml(item.date) +
			'</small></span>' +
			'</label>'
		);
	}

	function renderResults(items) {
		if (!items || !items.length) {
			$results.html('<p class="mmi-ra-muted">' + escapeHtml(mmiRaAdmin.i18n.noResults) + '</p>');
			updateButtons();
			return;
		}

		var html = '';
		var lastBucket = null;
		items.forEach(function (item) {
			if (item.bucket && item.bucket !== lastBucket) {
				lastBucket = item.bucket;
				var label =
					item.bucket === 'recent' ? mmiRaAdmin.i18n.recentFirst : mmiRaAdmin.i18n.older;
				html += '<div class="mmi-ra-results-divider">' + escapeHtml(label) + '</div>';
			}
			html += resultRow(item);
		});
		$results.html(html);
		updateButtons();
	}

	function ajaxSearch(term, action) {
		$results.html('<p class="mmi-ra-muted">' + escapeHtml(mmiRaAdmin.i18n.searching) + '</p>');
		$.post(mmiRaAdmin.ajaxUrl, {
			action: action,
			nonce: mmiRaAdmin.nonce,
			search: term,
			priority: getPriority(),
			post_id: postId
		})
			.done(function (response) {
				if (!response || !response.success) {
					$results.html('<p class="mmi-ra-error">' + escapeHtml(mmiRaAdmin.i18n.error) + '</p>');
					return;
				}
				renderResults(response.data.results || []);
			})
			.fail(function () {
				$results.html('<p class="mmi-ra-error">' + escapeHtml(mmiRaAdmin.i18n.error) + '</p>');
			});
	}

	function selectedItemHtml(item) {
		return (
			'<li class="mmi-ra-selected-item" data-id="' +
			item.id +
			'">' +
			'<span class="mmi-ra-drag" aria-hidden="true">☰</span>' +
			'<label class="mmi-ra-selected-check"><input type="checkbox" class="mmi-ra-selected-remove-cb" /></label>' +
			'<img src="' +
			escapeHtml(item.thumb) +
			'" alt="" width="40" height="40" loading="lazy" />' +
			'<span class="mmi-ra-selected-text"><strong>' +
			escapeHtml(item.title) +
			'</strong><small>' +
			escapeHtml(item.date) +
			'</small></span>' +
			'<button type="button" class="mmi-ra-remove-one button-link" aria-label="Remove">✕</button>' +
			'</li>'
		);
	}

	function addItemsFromResults() {
		var toAdd = [];
		$results.find('.mmi-ra-result-cb:checked:not(:disabled)').each(function () {
			var $row = $(this).closest('.mmi-ra-result-row');
			toAdd.push({
				id: parseInt($(this).val(), 10),
				title: $row.find('strong').text(),
				date: $row.find('small').text(),
				thumb: $row.find('img').attr('src')
			});
			$(this).prop('disabled', true).closest('.mmi-ra-result-row').addClass('is-added');
		});

		toAdd.forEach(function (item) {
			if (selectedIds().indexOf(item.id) === -1) {
				$selected.append(selectedItemHtml(item));
			}
		});
		syncHidden();
	}

	$search.on('input', function () {
		var term = $.trim($search.val());
		clearTimeout(debounceTimer);
		if (term.length < 2) {
			$results.empty();
			updateButtons();
			return;
		}
		debounceTimer = setTimeout(function () {
			ajaxSearch(term, 'mmi_ra_search_posts');
		}, 350);
	});

	$('input[name="mmi_ra_priority"]').on('change', function () {
		var term = $.trim($search.val());
		if (term.length >= 2) {
			ajaxSearch(term, 'mmi_ra_search_posts');
		}
	});

	$('#mmi-ra-auto-suggest').on('click', function () {
		ajaxSearch('', 'mmi_ra_auto_suggest');
	});

	$results.on('change', '.mmi-ra-result-cb', updateButtons);

	$addBtn.on('click', addItemsFromResults);

	$selected.on('click', '.mmi-ra-remove-one', function () {
		var $li = $(this).closest('.mmi-ra-selected-item');
		var id = $li.data('id');
		$li.remove();
		$results.find('.mmi-ra-result-cb[value="' + id + '"]').prop('disabled', false).prop('checked', false);
		syncHidden();
	});

	$removeBtn.on('click', function () {
		$selected.find('.mmi-ra-selected-remove-cb:checked').each(function () {
			var $li = $(this).closest('.mmi-ra-selected-item');
			var id = $li.data('id');
			$li.remove();
			$results.find('.mmi-ra-result-cb[value="' + id + '"]').prop('disabled', false).prop('checked', false);
		});
		syncHidden();
	});

	$('#mmi-ra-clear-all').on('click', function () {
		if (!window.confirm('Remove all related articles?')) {
			return;
		}
		$selected.empty();
		$results.find('.mmi-ra-result-cb').prop('disabled', false).prop('checked', false);
		syncHidden();
	});

	$selected.on('change', '.mmi-ra-selected-remove-cb', updateButtons);

	$selected.sortable({
		handle: '.mmi-ra-drag',
		axis: 'y',
		update: syncHidden
	});

	syncHidden();
})(jQuery);
