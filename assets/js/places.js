/* Medila Care — map legend toggles + kraj / město filter bar.
   Talks to WP Places Map through its DOM events: listens to `wppm:ready` /
   `wppm:change`, drives it with `wppm:filter`. Also filters the practice list
   ([medila_ambulance_regions]) on the same page. */
(function () {
	'use strict';

	function norm(s) {
		return String(s == null ? '' : s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}

	// The map that belongs to a control: same Divi section first, else the first on the page.
	function mapFor(el) {
		var sec = el.closest('.et_pb_section');
		var next = sec && sec.nextElementSibling;
		return (sec && sec.querySelector('.wppm-wrap')) ||
			(next && next.querySelector('.wppm-wrap')) ||
			document.querySelector('.wppm-wrap');
	}

	function send(map, detail) {
		if (map) map.dispatchEvent(new CustomEvent('wppm:filter', { detail: detail }));
	}

	// --- Legend: click = only this type, click again = all ------------------

	function initLegend(legend) {
		var map = mapFor(legend);
		var items = legend.querySelectorAll('[data-type]');
		items.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var on = btn.getAttribute('aria-pressed') === 'true';
				send(map, { type: on ? '' : btn.getAttribute('data-type') });
			});
		});
		if (map) {
			map.addEventListener('wppm:change', function (e) {
				items.forEach(function (btn) {
					var t = e.detail.type;
					btn.setAttribute('aria-pressed', t && t === btn.getAttribute('data-type') ? 'true' : 'false');
					btn.classList.toggle('is-dim', !!t && t !== btn.getAttribute('data-type'));
				});
			});
		}
	}

	// --- Filter bar ----------------------------------------------------------

	function initFilters(form) {
		var map = mapFor(form);
		var kraj = form.querySelector('[name="kraj"]');
		var mesto = form.querySelector('[name="mesto"]');
		var typ = form.querySelector('[name="typ"]');
		var reset = form.querySelector('[data-reset]');
		var count = form.querySelector('.mc-filters__count');
		var places = [];
		var regionNames = {};

		function inScope(p, withTown) {
			if (typ && typ.value && p.type !== typ.value) return false;
			if (kraj.value && p.region !== kraj.value) return false;
			if (withTown && mesto.value && norm(p.town) !== norm(mesto.value)) return false;
			return true;
		}

		function fillTowns() {
			var keep = mesto.value;
			var towns = {};
			places.forEach(function (p) {
				if (p.town && inScope(p, false)) towns[p.town] = true;
			});
			var list = Object.keys(towns).sort(function (a, b) { return a.localeCompare(b, 'cs'); });
			mesto.innerHTML = '<option value="">Všechna města</option>' + list.map(function (t) {
				return '<option>' + t.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }) + '</option>';
			}).join('');
			mesto.value = list.indexOf(keep) !== -1 ? keep : '';
		}

		function fillRegions() {
			// Only kraje that have something to show (for the chosen type).
			var has = {};
			places.forEach(function (p) {
				if (p.region && (!typ || !typ.value || p.type === typ.value)) has[p.region] = true;
			});
			Array.prototype.forEach.call(kraj.options, function (o) {
				if (!o.value) return;
				o.hidden = o.disabled = !has[o.value];
			});
			if (kraj.value && !has[kraj.value]) kraj.value = '';
		}

		function filterList() {
			var listEl = document.getElementById(form.getAttribute('data-list') || 'mc-regions');
			if (!listEl) return;
			var shown = 0;
			listEl.querySelectorAll('.mc-region').forEach(function (sec) {
				var any = false;
				sec.querySelectorAll('.mc-amb-card').forEach(function (card) {
					var ok = (!kraj.value || card.getAttribute('data-region') === kraj.value) &&
						(!mesto.value || norm(card.getAttribute('data-town')) === norm(mesto.value)) &&
						(!typ || !typ.value || typ.value === 'ordinace');
					card.hidden = !ok;
					if (ok) { any = true; shown++; }
				});
				sec.hidden = !any;
			});
			var empty = listEl.parentNode.querySelector('.mc-regions__empty');
			if (empty) empty.hidden = shown > 0;
		}

		function apply(fromRegion) {
			if (fromRegion) fillTowns();
			var active = kraj.value || mesto.value || (typ && typ.value);
			reset.hidden = !active;
			send(map, {
				type: typ ? typ.value : undefined,
				region: kraj.value,
				match: mesto.value ? { town: mesto.value } : null,
			});
			filterList();
		}

		kraj.addEventListener('change', function () { mesto.value = ''; apply(true); });
		mesto.addEventListener('change', function () { apply(false); });
		if (typ) typ.addEventListener('change', function () { fillRegions(); apply(true); });
		function clearAll() {
			kraj.value = ''; mesto.value = ''; if (typ) typ.value = '';
			fillRegions(); apply(true);
		}
		reset.addEventListener('click', clearAll);
		document.querySelectorAll('[data-mc-filters-reset]').forEach(function (b) { b.addEventListener('click', clearAll); });

		function ready(list, regions) {
			places = list || [];
			regionNames = regions || {};
			fillRegions();
			fillTowns();
			form.classList.add('is-ready');
		}

		if (map) {
			if (map.wppmPlaces) ready(map.wppmPlaces, map.wppmRegions);
			map.addEventListener('wppm:ready', function (e) { ready(e.detail.places, e.detail.regions); });
			// Keep the selects in step when the map itself changes the state
			// (kraj clicked in the map, legend toggled, region chip cleared).
			map.addEventListener('wppm:change', function (e) {
				var d = e.detail;
				var changed = false;
				if (kraj.value !== (d.region || '')) { kraj.value = d.region || ''; changed = true; }
				if (typ && typ.value !== (d.type || '')) { typ.value = d.type || ''; fillRegions(); changed = true; }
				if (changed) { fillTowns(); filterList(); }
				reset.hidden = !(kraj.value || mesto.value || (typ && typ.value));
				var n = d.count;
				count.textContent = (kraj.value || mesto.value || (typ && typ.value))
					? (n === 1 ? 'Na mapě je 1 místo' : n >= 2 && n <= 4 ? 'Na mapě jsou ' + n + ' místa' : 'Na mapě je ' + n + ' míst')
					: '';
			});
		} else {
			// No map on the page: the bar still filters the practice list by meta.
			form.classList.add('is-ready');
		}
	}

	function init() {
		document.querySelectorAll('[data-mc-legend]').forEach(initLegend);
		document.querySelectorAll('[data-mc-place-filters]').forEach(initFilters);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();
})();
