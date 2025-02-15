function update_service_ordering_callback(response) {
	if ( 'children' === response ) {
		window.location.reload();
		return;
	}
	jQuery('.spo-updating-row').removeClass('spo-updating-row');
	sortable_post_table.removeClass('spo-updating').sortable('enable');
	return;
	var changes = jQuery.parseJSON( response );
	var new_pos = changes.new_pos;
	for ( var key in new_pos ) {
		if ( 'next' === key ) {
			continue;
		}
		var inline_key = document.getElementById('inline_' + key);
		if ( null !== inline_key && new_pos.hasOwnProperty(key) ) {
			var dom_menu_order = inline_key.querySelector('.menu_order');
			if ( undefined !== new_pos[key]['menu_order'] ) {
				if ( null !== dom_menu_order ) {
					dom_menu_order.innerHTML = new_pos[key]['menu_order'];
				}
				var dom_post_parent = inline_key.querySelector('.post_parent');
				if ( null !== dom_post_parent ) {
					dom_post_parent.innerHTML = new_pos[key]['post_parent'];
				}
				var post_title = null;
				var dom_post_title = inline_key.querySelector('.post_title');
				if ( null !== dom_post_title ) {
					post_title = dom_post_title.innerHTML;
				}
				var dashes = 0;
				while ( dashes < new_pos[key]['depth'] ) {
					post_title = '&mdash; ' + post_title;
					dashes++;
				}
				var dom_row_title = inline_key.parentNode.querySelector('.row-title');
				if ( null !== dom_row_title && null !== post_title ) {
					dom_row_title.innerHTML = post_title;
				}
			} else if ( null !== dom_menu_order ) {
				dom_menu_order.innerHTML = new_pos[key];
			}
		}
	}
	if ( changes.next ) {
		jQuery.post( ajaxurl, {
			action: 'service_calculator_ordering',
			id: changes.next['id'],
			previd: changes.next['previd'],
			nextid: changes.next['nextid'],
			start: changes.next['start'],
			excluded: changes.next['excluded']
		}, update_service_ordering_callback );
	} else {
		jQuery('.spo-updating-row').removeClass('spo-updating-row');
		sortable_post_table.removeClass('spo-updating').sortable('enable');
	}
}
var sortable_post_table = jQuery(".wp-list-table tbody");
sortable_post_table.sortable({
	items: '> tr',
	cursor: 'move',
	axis: 'y',
	containment: 'table.widefat',
	cancel:	'.inline-edit-row',
	distance: 2,
	opacity: .8,
	tolerance: 'pointer',
	start: function(e, ui){
		if ( typeof(inlineEditPost) !== 'undefined' ) {
			inlineEditPost.revert();
		}
		ui.placeholder.height(ui.item.height());
	},
	helper: function(e, ui) {
		var children = ui.children();
		for ( var i=0; i<children.length; i++ ) {
			var selector = jQuery(children[i]);
			selector.width( selector.width() );
		};
		return ui;
	},
	stop: function(e, ui) {
		ui.item.children().css('width','');
	},
	update: function(e, ui) {
		sortable_post_table.sortable('disable').addClass('spo-updating');
		ui.item.addClass('spo-updating-row');
		var data = [];
		jQuery('#tblbrig > tbody  > tr').each(function(i, value) {
			data.push(value.id);
		});
		jQuery.post( ajaxurl, { action: 'service_calculator_ordering', data: data}, update_service_ordering_callback );
		var table_rows = document.querySelectorAll('tr.iedit'),
			table_row_count = table_rows.length;
		while( table_row_count-- ) {
			if ( 0 === table_row_count%2 ) {
				jQuery(table_rows[table_row_count]).addClass('alternate');
			} else {
				jQuery(table_rows[table_row_count]).removeClass('alternate');
			}
		}
	}
});