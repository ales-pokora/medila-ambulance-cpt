/* Medila Care — Kariéra: position type / location filters + "Zobrazit další pozice". */
(function () {
	'use strict';

	function norm(s) {
		return String(s == null ? '' : s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}
	function plural(n) {
		return n === 1 ? '1 pozice' : n >= 2 && n <= 4 ? n + ' pozice' : n + ' pozic';
	}

	function init(box) {
		var initial = parseInt(box.getAttribute('data-initial'), 10) || 0;
		var cards = Array.prototype.slice.call(box.querySelectorAll('.mc-job-card'));
		var typ = box.querySelector('[name="typ"]');
		var misto = box.querySelector('[name="misto"]');
		var reset = box.querySelector('[data-reset]');
		var count = box.querySelector('.mc-filters__count');
		var empty = box.querySelector('.mc-jobs__empty');
		var more = box.querySelector('[data-more]');
		var moreCount = box.querySelector('[data-more-count]');
		var expanded = !initial;

		function render() {
			var matching = cards.filter(function (c) {
				return (!typ || !typ.value || c.getAttribute('data-group') === typ.value) &&
					(!misto || !misto.value || norm(c.getAttribute('data-town')) === norm(misto.value));
			});
			var filtering = !!((typ && typ.value) || (misto && misto.value));
			// The "show more" cut only applies to the unfiltered list; a filter shows all its hits.
			var limit = expanded || filtering ? Infinity : initial;
			cards.forEach(function (c) { c.hidden = true; });
			matching.forEach(function (c, i) { c.hidden = i >= limit; });
			if (more) {
				var rest = matching.length - Math.min(limit, matching.length);
				more.parentNode.hidden = rest <= 0;
				if (moreCount) moreCount.textContent = '(' + rest + ')';
			}
			if (empty) empty.hidden = matching.length > 0;
			if (reset) reset.hidden = !filtering;
			if (count) count.textContent = filtering ? 'Nalezeno: ' + plural(matching.length) : '';
		}

		if (typ) typ.addEventListener('change', render);
		if (misto) misto.addEventListener('change', render);
		if (reset) reset.addEventListener('click', function () {
			if (typ) typ.value = '';
			if (misto) misto.value = '';
			render();
		});
		if (more) more.addEventListener('click', function () {
			var firstHidden = cards.filter(function (c) { return c.hidden; })[0];
			expanded = true;
			render();
			// Move focus to the first newly shown position for keyboard users.
			if (firstHidden) {
				var link = firstHidden.querySelector('a');
				if (link) link.focus({ preventScroll: true });
			}
		});
		render();
	}

	function boot() { document.querySelectorAll('[data-mc-jobs]').forEach(init); }
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
	else boot();
})();
