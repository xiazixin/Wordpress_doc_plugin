/**
 * Docs Nav admin — dynamic add/remove of project/group/doc rows and
 * drag-to-reorder via jQuery UI Sortable (bundled with WordPress).
 *
 * New rows get index keys like "n1001"; PHP re-indexes with array_values on save.
 */
jQuery(function ($) {
	'use strict';

	var counter = 1000;

	function nextIndex() {
		return 'n' + counter++;
	}

	var baseSort = {
		handle: '.drag-handle',
		axis: 'y',
		opacity: 0.7,
		cursor: 'move',
		placeholder: 'docs-nav-sortable-placeholder'
	};
	var projectSort = $.extend({}, baseSort, { items: '> .docs-nav-project' });
	var groupSort = $.extend({}, baseSort, { items: '> .docs-nav-group' });
	var docSort = $.extend({}, baseSort, { items: '> .docs-nav-doc' });

	function initGroupSortable($el) { $el.sortable(groupSort); }
	function initDocSortable($el) { $el.sortable(docSort); }

	$('#docs-nav-projects').sortable(projectSort);
	$('.docs-nav-groups').each(function () { initGroupSortable($(this)); });
	$('.docs-nav-docs').each(function () { initDocSortable($(this)); });

	// Fill a hidden <script type="text/html"> template, replacing every
	// __P__/__G__/__D__ placeholder with real index keys.
	function fill(templateId, replacements) {
		var html = $(templateId).html();
		$.each(replacements, function (key, value) {
			html = html.split(key).join(value);
		});
		return html;
	}

	function updateEmptyState() {
		$('#docs-nav-empty').toggle($('#docs-nav-projects .docs-nav-project').length === 0);
	}

	$('#docs-nav-add-project').on('click', function () {
		var $node = $(fill('#tmpl-docs-nav-project', { __P__: nextIndex() }));
		$('#docs-nav-projects').append($node);
		initGroupSortable($node.find('.docs-nav-groups'));
		$node.find('.docs-nav-title').trigger('focus');
		updateEmptyState();
	});

	$(document).on('click', '.docs-nav-add-group', function () {
		var $project = $(this).closest('.docs-nav-project');
		var pIndex = String($project.data('index'));
		var $node = $(fill('#tmpl-docs-nav-group', { __P__: pIndex, __G__: nextIndex() }));
		$project.children('.docs-nav-groups').append($node);
		initDocSortable($node.find('.docs-nav-docs'));
		$node.find('.docs-nav-title').trigger('focus');
	});

	$(document).on('click', '.docs-nav-add-doc', function () {
		var $group = $(this).closest('.docs-nav-group');
		var $node = $(fill('#tmpl-docs-nav-doc', {
			__P__: String($group.closest('.docs-nav-project').data('index')),
			__G__: String($group.data('index')),
			__D__: nextIndex()
		}));
		$group.children('.docs-nav-docs').append($node);
	});

	function rowHasContent($row, childSelector) {
		return $.trim($row.children('.docs-nav-row-head').find('.docs-nav-title').val()) !== ''
			|| $row.find(childSelector).length > 0;
	}

	$(document).on('click', '.docs-nav-remove-project', function () {
		var $project = $(this).closest('.docs-nav-project');
		if (!rowHasContent($project, '.docs-nav-group')
			|| window.confirm('Remove this project and all its groups and documentations?')) {
			$project.remove();
			updateEmptyState();
		}
	});

	$(document).on('click', '.docs-nav-remove-group', function () {
		var $group = $(this).closest('.docs-nav-group');
		if (!rowHasContent($group, '.docs-nav-doc')
			|| window.confirm('Remove this group and its documentations?')) {
			$group.remove();
		}
	});

	$(document).on('click', '.docs-nav-remove-doc', function () {
		$(this).closest('.docs-nav-doc').remove();
	});
});
