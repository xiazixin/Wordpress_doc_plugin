/**
 * Wordpress Doc Plugin — builds the on-page table of contents from h2/h3 headings
 * in .docs-content, with scrollspy highlighting.
 */
(function () {
	'use strict';

	var content = document.querySelector('.docs-content');
	var toc = document.getElementById('docs-toc');
	if (!content || !toc) {
		return;
	}

	var headings = content.querySelectorAll('h2, h3');
	if (!headings.length) {
		toc.innerHTML = '<p class="docs-toc-empty">No sections on this page yet.</p>';
		return;
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function slugify(text, index) {
		var base = text.toLowerCase()
			.replace(/[^\w\u4e00-\u9fff -]/g, '')
			.replace(/\s+/g, '-')
			.replace(/-+/g, '-')
			.replace(/^-+|-+$/g, '');
		if (!base) {
			base = 'section';
		}
		var seen = slugify.used[base];
		if (seen) {
			slugify.used[base]++;
			base = base + '-' + seen;
		} else {
			slugify.used[base] = 1;
		}
		return base;
	}
	slugify.used = {};

	// Assign ids and group h3s under their h2.
	var h2s = [];
	var currentH2 = -1;
	headings.forEach(function (h, i) {
		if (!h.id) {
			h.id = slugify(h.textContent, i);
		}
		if (h.tagName === 'H2') {
			currentH2++;
			h2s.push({ id: h.id, text: h.textContent, subs: [] });
		} else if (currentH2 >= 0) {
			h2s[currentH2].subs.push({ id: h.id, text: h.textContent });
		}
	});

	var html = '<ul>';
	h2s.forEach(function (h2) {
		html += '<li class="toc-h2"><a href="#' + h2.id + '">' + escapeHtml(h2.text) + '</a>';
		if (h2.subs.length) {
			html += '<ul>';
			h2.subs.forEach(function (h3) {
				html += '<li class="toc-h3"><a href="#' + h3.id + '">' + escapeHtml(h3.text) + '</a></li>';
			});
			html += '</ul>';
		}
		html += '</li>';
	});
	html += '</ul>';
	toc.innerHTML = html;

	// Scrollspy: highlight the section currently in view.
	var links = toc.querySelectorAll('a');
	var idMap = {};
	headings.forEach(function (h) {
		idMap[h.id] = h;
	});
	var ids = Object.keys(idMap);

	function onScroll() {
		var probe = window.scrollY + 140;
		var current = ids[0];
		ids.forEach(function (id) {
			var top = idMap[id].getBoundingClientRect().top + window.scrollY;
			if (top <= probe) {
				current = id;
			}
		});
		links.forEach(function (a) {
			a.parentElement.classList.toggle('active', a.getAttribute('href') === '#' + current);
		});
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	onScroll();
})();
