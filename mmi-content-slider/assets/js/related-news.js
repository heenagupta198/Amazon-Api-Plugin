(function () {
	var cfg = window.MmiCslNews || {};

	function esc(value) {
		return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
		});
	}

	function safeUrl(value) {
		try {
			var url = new URL(String(value), window.location.origin);
			if (url.protocol === 'http:' || url.protocol === 'https:') {
				return url.href;
			}
		} catch (e) {
			return '';
		}
		return '';
	}

	function formatDate(iso) {
		if (!iso) {
			return '';
		}
		var date = new Date(iso);
		if (isNaN(date.getTime())) {
			return '';
		}
		return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
	}

	function placeMount(mount) {
		var anchor = document.querySelector('#related-section-woo-area, .related-woo-area');
		if (anchor && anchor.parentNode && mount.parentNode !== anchor.parentNode) {
			anchor.parentNode.insertBefore(mount, anchor);
			return;
		}
		if (anchor && anchor.parentNode && mount.nextSibling !== anchor) {
			anchor.parentNode.insertBefore(mount, anchor);
		}
	}

	function cardHtml(item) {
		var url = safeUrl(item.url);
		if (!url) {
			return '';
		}
		var title = esc(item.title || '');
		var image = safeUrl(item.image || '');
		var dateLabel = formatDate(item.date);
		var img = image
			? '<img src="' + esc(image) + '" alt="' + title + '" loading="lazy" width="280" height="158" />'
			: '';
		var time = dateLabel
			? '<time class="news-box-date" datetime="' + esc(item.date) + '">' + esc(dateLabel) + '</time>'
			: '';
		return (
			'<div class="swiper-slide"><article class="news-box">' +
			'<a href="' +
			esc(url) +
			'" class="news-box-link" rel="bookmark">' +
			img +
			'<h4 class="news-box-title">' +
			title +
			'</h4>' +
			time +
			'</a></article></div>'
		);
	}

	function render(mount, posts) {
		var heading = esc(cfg.heading || 'Related Articles');
		var slides = posts.map(cardHtml).filter(Boolean).join('');
		if (!slides) {
			return false;
		}
		mount.innerHTML =
			'<section class="related-news-slider mmi-device-latest-news" aria-labelledby="mmi-latest-news-heading">' +
			'<h3 id="mmi-latest-news-heading" class="mmi-latest-news-title">' +
			heading +
			'</h3>' +
			'<div class="swiper mmi-related-news-swiper"><div class="swiper-wrapper">' +
			slides +
			'</div>' +
			'<button type="button" class="swiper-button-prev" aria-label="' +
			esc(cfg.prevLabel || 'Previous') +
			'"></button>' +
			'<button type="button" class="swiper-button-next" aria-label="' +
			esc(cfg.nextLabel || 'Next') +
			'"></button></div></section>';
		return true;
	}

	function initSwiper(mount) {
		if (typeof Swiper === 'undefined') {
			return;
		}
		mount.querySelectorAll('.mmi-related-news-swiper').forEach(function (el) {
			if (el.swiper) {
				return;
			}
			var section = el.closest('.related-news-slider');
			new Swiper(el, {
				slidesPerView: 2,
				spaceBetween: 16,
				loop: false,
				speed: 500,
				watchOverflow: true,
				navigation: {
					nextEl: section ? section.querySelector('.swiper-button-next') : null,
					prevEl: section ? section.querySelector('.swiper-button-prev') : null
				},
				breakpoints: {
					640: { slidesPerView: 3 },
					1024: { slidesPerView: 4 },
					1280: { slidesPerView: 5 }
				}
			});
		});
	}

	function fetchAndRender(mount) {
		var productId = mount.getAttribute('data-product-id') || cfg.productId;
		var restBase = cfg.restUrl || '';
		if (!productId || !restBase) {
			return;
		}
		fetch(restBase + encodeURIComponent(productId) + '?per_page=12', {
			credentials: 'omit',
			headers: { Accept: 'application/json' }
		})
			.then(function (res) {
				if (!res.ok) {
					throw new Error('news rest ' + res.status);
				}
				return res.json();
			})
			.then(function (data) {
				var posts = data && Array.isArray(data.posts) ? data.posts : [];
				if (!posts.length) {
					return;
				}
				if (render(mount, posts)) {
					placeMount(mount);
					initSwiper(mount);
				}
			})
			.catch(function () {
				/* keep page usable if news REST is blocked */
			});
	}

	function boot() {
		var mount = document.getElementById('mmi-latest-news-mount');
		if (!mount) {
			return;
		}
		placeMount(mount);
		if (mount.querySelector('.swiper-slide')) {
			initSwiper(mount);
			return;
		}
		fetchAndRender(mount);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
