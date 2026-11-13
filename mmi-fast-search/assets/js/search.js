/**
 * Client-side instant search (no server round-trips while typing).
 */
(function () {
	'use strict';

	var cfg = window.MmiFastSearch || {};
	var i18n = cfg.i18n || {};

	var state = {
		open: false,
		loaded: false,
		loading: false,
		items: [],
		norm: [],
		timer: null,
		root: null,
		input: null
	};

	var TAGdiv_SELECTORS = [
		'.td-search-icon',
		'.td-header-search-wrap .td-icon-search',
		'.tdb-header-search-wrap',
		'.tdb-header-search-button',
		'.tdb-head-search-btn',
		'.tdb_mobile_search',
		'.tdb-header-search-button-mob',
		'#td-header-search-button',
		'.header-search-wrap',
		'.td-search-wrap .td-icon-search'
	].join(',');

	function esc(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function normalize(str) {
		return String(str || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^\p{L}\p{N}\s]+/gu, ' ')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function formatDate(iso) {
		if (!iso) return '';
		try {
			var d = new Date(iso);
			if (isNaN(d.getTime())) return '';
			return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
		} catch (e) {
			return '';
		}
	}

	function buildUi() {
		if (state.root) return;

		var root = document.createElement('div');
		root.className = 'mmi-fs-root';
		root.setAttribute('role', 'dialog');
		root.setAttribute('aria-modal', 'true');
		root.setAttribute('aria-label', i18n.open || 'Search');
		root.innerHTML =
			'<div class="mmi-fs-backdrop" data-mmi-fs-close></div>' +
			'<div class="mmi-fs-dialog">' +
			'<button type="button" class="mmi-fs-close" data-mmi-fs-close aria-label="' + esc(i18n.close || 'Close') + '">&times;</button>' +
			'<form class="mmi-fs-form" action="' + esc(cfg.searchUrl || '/') + '" method="get">' +
			'<div class="mmi-fs-input-wrap">' +
			'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/><path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>' +
			'<input class="mmi-fs-input" type="search" name="s" autocomplete="off" spellcheck="false" placeholder="' + esc(i18n.placeholder || '') + '" />' +
			'</div>' +
			'<button class="mmi-fs-submit" type="submit">Search</button>' +
			'</form>' +
			'<div class="mmi-fs-body">' +
			'<div class="mmi-fs-status" data-mmi-fs-status>' + esc(i18n.empty || '') + '</div>' +
			'<div class="mmi-fs-label" data-mmi-fs-label hidden>' + esc(i18n.posts || 'Posts') + '</div>' +
			'<div class="mmi-fs-grid" data-mmi-fs-grid></div>' +
			'</div>' +
			'<div class="mmi-fs-footer" data-mmi-fs-footer hidden>' +
			'<a class="mmi-fs-view-all" data-mmi-fs-view-all href="#">' + esc(i18n.viewAll || '') + '</a>' +
			'</div>' +
			'</div>';

		document.body.appendChild(root);
		state.root = root;
		state.input = root.querySelector('.mmi-fs-input');

		root.addEventListener('click', function (e) {
			if (e.target.closest('[data-mmi-fs-close]')) {
				close();
			}
		});

		root.querySelector('form').addEventListener('submit', function () {
			close();
		});

		state.input.addEventListener('input', onInput);
	}

	function setStatus(msg) {
		var el = state.root && state.root.querySelector('[data-mmi-fs-status]');
		var grid = state.root && state.root.querySelector('[data-mmi-fs-grid]');
		var label = state.root && state.root.querySelector('[data-mmi-fs-label]');
		var footer = state.root && state.root.querySelector('[data-mmi-fs-footer]');
		if (!el) return;
		if (msg) {
			el.hidden = false;
			el.textContent = msg;
		} else {
			el.hidden = true;
		}
		if (grid) grid.innerHTML = '';
		if (label) label.hidden = true;
		if (footer) footer.hidden = true;
	}

	function renderResults(list, query) {
		var grid = state.root.querySelector('[data-mmi-fs-grid]');
		var label = state.root.querySelector('[data-mmi-fs-label]');
		var footer = state.root.querySelector('[data-mmi-fs-footer]');
		var viewAll = state.root.querySelector('[data-mmi-fs-view-all]');

		if (!list.length) {
			setStatus(i18n.noResults || 'No results');
			return;
		}

		setStatus('');
		label.hidden = false;
		footer.hidden = false;

		var base = cfg.searchUrl || '/';
		var sep = base.indexOf('?') >= 0 ? '&' : '?';
		viewAll.href = base + sep + 's=' + encodeURIComponent(query);

		var html = '';
		for (var i = 0; i < list.length; i++) {
			var item = list[i];
			var thumb = item.th
				? '<img src="' + esc(item.th) + '" alt="" loading="lazy" decoding="async" width="160" height="100" />'
				: '';
			html +=
				'<a class="mmi-fs-card" href="' +
				esc(item.u) +
				'">' +
				'<div class="mmi-fs-thumb">' +
				thumb +
				'</div>' +
				'<span class="mmi-fs-title">' +
				esc(item.t) +
				'</span>' +
				'<span class="mmi-fs-date">' +
				esc(formatDate(item.d)) +
				'</span>' +
				'</a>';
		}
		grid.innerHTML = html;
	}

	function scoreItem(normTitle, tokens, rawTitle) {
		if (!tokens.length) return 0;
		var s = 0;
		var joined = tokens.join(' ');

		if (normTitle === joined) {
			s += 1000;
		} else if (normTitle.indexOf(joined) === 0) {
			s += 800;
		} else if (normTitle.indexOf(joined) >= 0) {
			s += 500;
		}

		for (var t = 0; t < tokens.length; t++) {
			var tok = tokens[t];
			if (!tok) continue;
			if (normTitle.indexOf(' ' + tok) >= 0 || normTitle.indexOf(tok) === 0) {
				s += 120;
			} else if (normTitle.indexOf(tok) >= 0) {
				s += 40;
			} else {
				return 0;
			}
		}

		s -= Math.min(80, Math.floor((rawTitle.length || 0) / 12));

		return s;
	}

	function searchLocal(q) {
		var tokens = normalize(q).split(' ').filter(Boolean);
		if (!tokens.length) {
			setStatus(i18n.empty || '');
			return;
		}

		var limit = cfg.limit || 12;
		var scored = [];

		for (var i = 0; i < state.items.length; i++) {
			var item = state.items[i];
			var sc = scoreItem(state.norm[i], tokens, item.t);
			if (sc > 0) {
				scored.push({ item: item, sc: sc, idx: item.i || 0 });
			}
		}

		scored.sort(function (a, b) {
			if (b.sc !== a.sc) return b.sc - a.sc;
			return (b.idx || 0) - (a.idx || 0);
		});

		renderResults(
			scored.slice(0, limit).map(function (x) {
				return x.item;
			}),
			q
		);
	}

	function onInput() {
		var q = state.input.value;
		window.clearTimeout(state.timer);
		state.timer = window.setTimeout(function () {
			searchLocal(q);
		}, cfg.debounce || 40);
	}

	function loadIndex() {
		if (state.loaded || state.loading || !cfg.indexUrl) {
			return Promise.resolve();
		}
		state.loading = true;
		setStatus(i18n.loading || 'Loading…');

		return fetch(cfg.indexUrl, { credentials: 'same-origin', cache: 'force-cache' })
			.then(function (res) {
				if (!res.ok) throw new Error('index');
				return res.json();
			})
			.then(function (data) {
				var items = (data && data.items) || [];
				state.items = items;
				state.norm = items.map(function (it) {
					return normalize(it.t);
				});
				state.loaded = true;
				state.loading = false;
				setStatus(i18n.empty || '');
			})
			.catch(function () {
				state.loading = false;
				setStatus('Could not load search index.');
			});
	}

	function open(prefill) {
		buildUi();
		state.open = true;
		state.root.classList.add('is-open');
		document.body.classList.add('mmi-fs-lock');

		loadIndex().then(function () {
			if (typeof prefill === 'string' && prefill) {
				state.input.value = prefill;
				searchLocal(prefill);
			} else {
				setStatus(i18n.empty || '');
			}
			state.input.focus();
			state.input.select();
		});
	}

	function close() {
		if (!state.root) return;
		state.open = false;
		state.root.classList.remove('is-open');
		document.body.classList.remove('mmi-fs-lock');
	}

	function hijackTagdiv() {
		if (!cfg.replaceTagdiv) return;

		document.addEventListener(
			'click',
			function (e) {
				var el = e.target.closest(TAGdiv_SELECTORS);
				if (!el) return;
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				open('');
			},
			true
		);
	}

	function keyboard() {
		document.addEventListener('keydown', function (e) {
			if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
				e.preventDefault();
				open('');
				return;
			}
			if (e.key === 'Escape' && state.open) {
				close();
			}
		});
	}

	function boot() {
		buildUi();
		hijackTagdiv();
		keyboard();
		if (cfg.preload) {
			loadIndex();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.MmiFastSearchOpen = open;
})();
