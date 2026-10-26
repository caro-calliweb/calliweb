(function () {
	var ELEMENT_NAME = 'ect-bricks-events-loop';
	var PRO_OPTION_HITS = [
		{
			selector: '[data-controlkey="layout_template"] .dropdown > li:nth-child(2)',
			urlKey: 'gridUrl',
			fallback: 'https://eventscalendaraddons.com/bricks/grid/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=bricks_grid_editor_panel'
		}
	];
	var GROUP_MAP = {
		'layouts': 'layouts',
		'events query': 'event_query',
		'elements': 'elements',
		'dynamic messages': 'dynamic_messages',
		'pagination': 'pagination',
		'events cards': 'events_card',
		'date': 'style1_date',
		'featured image': 'featured_image'
	};
	var STYLE_GROUPS = {
		events_card: 1,
		style1_date: 1,
		featured_image: 1
	};
	var syncTimer = null;

	function getProUrl(key, fallback) {
		if (window.ectBricksBuilderPanel && window.ectBricksBuilderPanel[key]) {
			return window.ectBricksBuilderPanel[key];
		}
		return fallback;
	}

	function onProLockedOption(e) {
		if (!e.target.closest) {
			return;
		}
		var i;
		for (i = 0; i < PRO_OPTION_HITS.length; i++) {
			var hit = PRO_OPTION_HITS[i];
			if (!e.target.closest(hit.selector)) {
				continue;
			}
			e.preventDefault();
			e.stopImmediatePropagation();
			if (e.type === 'click') {
				window.open(getProUrl(hit.urlKey, hit.fallback), '_blank', 'noopener,noreferrer');
			}
			return;
		}
	}

	function getStyleSettingsLabel() {
		return (window.ectBricksBuilderPanel && window.ectBricksBuilderPanel.styleSettingsLabel)
			? window.ectBricksBuilderPanel.styleSettingsLabel
			: 'Events Shortcodes Style Settings';
	}

	function getActiveElementName() {
		try {
			if (window.bricksData && window.bricksData.activeElement && window.bricksData.activeElement.name) {
				return String(window.bricksData.activeElement.name);
			}
		} catch (e) { /* ignore */ }
		return '';
	}

	function isEventsWidgetPanel(panel) {
		if (getActiveElementName() === ELEMENT_NAME) {
			return true;
		}
		if (!panel) {
			return false;
		}
		var text = (panel.textContent || '').toLowerCase();
		return text.indexOf('events cards') !== -1 && text.indexOf('events query') !== -1;
	}

	function groupTitleText(group) {
		var title = group.querySelector('.control-group-title');
		if (!title) {
			return '';
		}
		var clone = title.cloneNode(true);
		clone.querySelectorAll('.icon, .arrow, .badge, svg, i, button').forEach(function (el) {
			el.remove();
		});
		return (clone.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
	}

	function removePluginSettingsLabels(panel) {
		if (!panel || !panel.querySelectorAll) {
			return;
		}
		panel.querySelectorAll('.ect-bricks-plugin-settings-label').forEach(function (el) {
			el.remove();
		});
	}

	function insertPluginSettingsLabel(panel) {
		if (!panel) {
			return;
		}
		var first = null;
		panel.querySelectorAll('.control-group[data-ect-bricks-group]').forEach(function (group) {
			if (first) {
				return;
			}
			if (STYLE_GROUPS[group.getAttribute('data-ect-bricks-group')]) {
				first = group;
			}
		});
		var existing = panel.querySelectorAll('.ect-bricks-plugin-settings-label');
		if (!first) {
			existing.forEach(function (el) { el.remove(); });
			return;
		}
		var inGroup = first.firstElementChild && first.firstElementChild.classList.contains('ect-bricks-plugin-settings-label')
			? first.firstElementChild
			: null;
		if (inGroup) {
			inGroup.textContent = getStyleSettingsLabel();
			existing.forEach(function (el) {
				if (el !== inGroup) {
					el.remove();
				}
			});
			return;
		}
		existing.forEach(function (el) { el.remove(); });
		var label = document.createElement('div');
		label.className = 'ect-bricks-plugin-settings-label';
		label.setAttribute('role', 'presentation');
		label.textContent = getStyleSettingsLabel();
		first.insertBefore(label, first.firstChild);
	}

	function tagPluginControlGroups(panel) {
		if (!panel || !panel.querySelectorAll) {
			return;
		}
		panel.querySelectorAll('.control-group').forEach(function (group) {
			var text = groupTitleText(group);
			if (GROUP_MAP[text]) {
				group.setAttribute('data-ect-bricks-group', GROUP_MAP[text]);
			}
		});
		insertPluginSettingsLabel(panel);
		decorateGetProDescriptions(panel);
		applyProLocks(panel);
	}

	function descriptionNodes(control) {
		if (!control || !control.querySelectorAll) {
			return [];
		}
		return control.querySelectorAll('.description, .control-description');
	}

	function decorateGetProDescriptions(panel) {
		panel.querySelectorAll('[data-controlkey="layout_template"]').forEach(function (control) {
			descriptionNodes(control).forEach(function (el) {
				if (el.querySelector('.ect-bricks-get-pro-link')) {
					return;
				}
				if (/<a\s/i.test(el.textContent || '')) {
					el.innerHTML = el.textContent;
				}
			});
		});
	}

	function getLockGroups() {
		if (window.ectBricksBuilderPanel && Array.isArray(window.ectBricksBuilderPanel.proLockGroups)) {
			return window.ectBricksBuilderPanel.proLockGroups;
		}
		return [];
	}

	function controlKey(el) {
		return el && el.getAttribute ? (el.getAttribute('data-controlkey') || '') : '';
	}

	function collectLockNodes(panel, keys) {
		var wanted = {};
		var i;
		for (i = 0; i < keys.length; i++) {
			wanted[keys[i]] = true;
		}
		var nodes = [];
		panel.querySelectorAll('[data-controlkey]').forEach(function (el) {
			if (!wanted[controlKey(el)] || !el.offsetParent) {
				return;
			}
			nodes.push(el);
		});
		return nodes;
	}

	function clearProLocks(panel) {
		if (!panel || !panel.querySelectorAll) {
			return;
		}
		panel.querySelectorAll('.ect-bricks-pro-lock-item').forEach(function (el) {
			el.classList.remove('ect-bricks-pro-lock-item', 'ect-bricks-pro-lock--first', 'ect-bricks-pro-lock--last');
		});
		panel.querySelectorAll('.ect-bricks-pro-lock__cta').forEach(function (el) {
			el.remove();
		});
	}

	function applyProLocks(panel) {
		var groups = getLockGroups();
		var fallbackUrl = getProUrl(
			'upgradeUrl',
			'https://eventscalendaraddons.com/plugin/events-shortcodes-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=bricks_pro_lock_editor_panel'
		);
		var label = (window.ectBricksBuilderPanel && window.ectBricksBuilderPanel.upgradeLabel)
			? window.ectBricksBuilderPanel.upgradeLabel
			: 'Upgrade to Pro';
		var used = {};
		var g;

		for (g = 0; g < groups.length; g++) {
			var keys = (groups[g] && groups[g].keys) ? groups[g].keys : [];
			var url = (groups[g] && groups[g].url) ? groups[g].url : fallbackUrl;
			var nodes = collectLockNodes(panel, keys);
			if (!nodes.length) {
				continue;
			}
			nodes.forEach(function (n, i) {
				used[controlKey(n)] = true;
				n.classList.add('ect-bricks-pro-lock-item');
				n.classList.toggle('ect-bricks-pro-lock--first', i === 0);
				n.classList.toggle('ect-bricks-pro-lock--last', i === nodes.length - 1);
			});
			var last = nodes[nodes.length - 1];
			var cta = last.querySelector('.ect-bricks-pro-lock__cta');
			if (!cta) {
				cta = document.createElement('a');
				cta.className = 'ect-bricks-pro-lock__cta';
				cta.target = '_blank';
				cta.rel = 'noopener noreferrer';
				last.appendChild(cta);
			}
			if (cta.getAttribute('href') !== url) {
				cta.setAttribute('href', url);
			}
			if (cta.textContent !== label) {
				cta.textContent = label;
			}
		}

		panel.querySelectorAll('.ect-bricks-pro-lock-item').forEach(function (el) {
			if (!used[controlKey(el)]) {
				el.classList.remove('ect-bricks-pro-lock-item', 'ect-bricks-pro-lock--first', 'ect-bricks-pro-lock--last');
			}
		});
		panel.querySelectorAll('.ect-bricks-pro-lock__cta').forEach(function (el) {
			var host = el.closest('[data-controlkey]');
			if (!host || !host.classList.contains('ect-bricks-pro-lock--last')) {
				el.remove();
			}
		});
	}

	function syncPanelFlags() {
		var panel = document.getElementById('bricks-panel');
		if (!panel) {
			return;
		}
		if (isEventsWidgetPanel(panel)) {
			panel.setAttribute('data-ect-bricks-events', '1');
			tagPluginControlGroups(panel);
		} else {
			panel.removeAttribute('data-ect-bricks-events');
			panel.querySelectorAll('.control-group[data-ect-bricks-group]').forEach(function (group) {
				group.removeAttribute('data-ect-bricks-group');
			});
			removePluginSettingsLabels(panel);
			clearProLocks(panel);
		}
	}

	function scheduleSync() {
		if (syncTimer) {
			cancelAnimationFrame(syncTimer);
		}
		syncTimer = requestAnimationFrame(function () {
			syncPanelFlags();
			maybeSeedActiveParts();
		});
	}

	function bricksGlobalProps() {
		var nodes = document.querySelectorAll('.brx-body.main, .brx-body, [data-v-app]');
		var i;
		var fallback = null;
		for (i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			var app = el.__vue_app__ || (el.__vueParentComponent && el.__vueParentComponent.appContext && el.__vueParentComponent.appContext.app);
			var gp = app && app.config && app.config.globalProperties;
			if (!gp) {
				continue;
			}
			if (typeof gp.$_updateSetting === 'function') {
				return gp;
			}
			if (!fallback) {
				fallback = gp;
			}
		}
		return fallback;
	}

	function getActiveEventsElement() {
		try {
			var gp = bricksGlobalProps();
			var el = gp && gp.$_state && gp.$_state.activeElement;
			if (el && el.name === ELEMENT_NAME) {
				return el;
			}
		} catch (e) { /* ignore */ }
		return null;
	}

	function hasParts(parts) {
		if (!Array.isArray(parts) || !parts.length) {
			return false;
		}
		return parts.some(function (row) {
			return row && String(row.part || '').trim() !== '';
		});
	}

	function cloneDefaultParts(key) {
		var map = (window.ectBricksBuilderPanel && window.ectBricksBuilderPanel.defaultParts) || {};
		var defaults = map[key];
		if (!Array.isArray(defaults) || !defaults.length) {
			return null;
		}
		try {
			return JSON.parse(JSON.stringify(defaults));
		} catch (e) {
			return null;
		}
	}

	function maybeSeedActiveParts() {
		var el = getActiveEventsElement();
		var gp = bricksGlobalProps();
		if (!el || !el.settings || !el.id || !gp || typeof gp.$_updateSetting !== 'function') {
			return;
		}
		var map = (window.ectBricksBuilderPanel && window.ectBricksBuilderPanel.defaultParts) || {};
		var key;
		for (key in map) {
			if (!Object.prototype.hasOwnProperty.call(map, key)) {
				continue;
			}
			if (hasParts(el.settings[key]) || (Array.isArray(el.settings[key]) && !el.settings[key].length)) {
				continue;
			}
			var defaults = cloneDefaultParts(key);
			if (!defaults) {
				continue;
			}
			try {
				gp.$_updateSetting(el.id, key, defaults);
			} catch (e) { /* retry on next tick */ }
		}
	}

	function observePanel() {
		var panel = document.getElementById('bricks-panel');
		if (!panel || panel.getAttribute('data-ect-bricks-observed') === '1') {
			return;
		}
		panel.setAttribute('data-ect-bricks-observed', '1');
		var mo = new MutationObserver(scheduleSync);
		mo.observe(panel, { childList: true, subtree: true });
	}

	function onGetProLink(e) {
		var link = e.target.closest && e.target.closest('.ect-bricks-get-pro-link, .ect-bricks-pro-lock__cta');
		if (!link || !link.closest('#bricks-panel')) {
			return;
		}
		e.preventDefault();
		e.stopImmediatePropagation();
		if (e.type === 'click') {
			var href = link.getAttribute('href');
			if (href) {
				window.open(href, '_blank', 'noopener,noreferrer');
			}
		}
	}

	document.addEventListener('pointerdown', onProLockedOption, true);
	document.addEventListener('mousedown', onProLockedOption, true);
	document.addEventListener('click', onProLockedOption, true);
	document.addEventListener('pointerdown', onGetProLink, true);
	document.addEventListener('mousedown', onGetProLink, true);
	document.addEventListener('click', onGetProLink, true);

	observePanel();
	syncPanelFlags();
	maybeSeedActiveParts();
	window.setInterval(function () {
		observePanel();
		syncPanelFlags();
		maybeSeedActiveParts();
	}, 400);
})();
