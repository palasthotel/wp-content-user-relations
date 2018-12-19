(function($, api, data, builder) {

	/**
	 * events on body
	 */
	const HOOKS = {
		READY: 'user_relations_ready',
		RELATION_TYPESTATE_SELECTION_CHANGE: 'relation_typestate_selection_change',
		USER_RELATION_ADD: 'user_relation_add',
		USER_RELATION_REMOVE: 'user_relation_remove',
	};

	// --------------------------------------------------------
	// quick look for existing object functions
	// --------------------------------------------------------
	const functionExists = (name, obj) => {
		if (typeof obj === typeof undefined) {
			return typeof builder[name] ===
				'function';
		}
		return typeof obj[name] === 'function';
	};
	const functionNotExists = (name, obj) => {
		return !functionExists(name, obj);
	};

	// ----------------------------
	// local vars
	// ----------------------------
	const post_id = data.post_id;
	const i18n = data.i18n;
	const relations = data.relations;
	const typestates = data.typestates;
	const POST = data.POST;
	const ACTION = data.ACTION;
	const ready_to_save_value = data.ready_to_save_value;
	const links = data.links;

	// ----------------------------
	// enrich builder with data
	// ----------------------------
	const data_keys = Object.keys(data);
	for (let i = 0; i < data_keys.length; i++) {
		builder[data_keys[i]] = data[data_keys[i]];
	}

	// ----------------------------
	// relations table builder
	// ----------------------------

	/**
	 * build a single hidden field
	 * @param name
	 * @param value
	 * @return {element}
	 */
	if (functionNotExists('buildHiddenField')) {
		builder.buildHiddenField = function(name, value) {
			return $('<input />')
				.val(value)
				.attr('name', name)
				.attr('type', 'hidden');
		};
	}

	/**
	 * build hidden fields for post_save action
	 * @param relation
	 * @param action
	 * @return [element]
	 */
	if (functionNotExists('buildHiddenFields')) {
		builder.buildHiddenFields = function(relation, action = '') {
			return [
				this.buildHiddenField(`${POST.user_ids}[]`, relation.user_id),
				this.buildHiddenField(`${POST.typestate_ids}[]`,
					relation.typestate_id),
				this.buildHiddenField(`${POST.actions}[]`, ''),
			];
		};
	}

	/**
	 * build a remove button
	 * @return {element}
	 */
	if (functionNotExists('buildRemove')) {
		builder.buildRemove = function() {
			return $('<a></a>').text(i18n.remove).addClass('remove');
		};
	}

	/**
	 * build user relation item row
	 * @param relation
	 * @return {element}
	 */
	if (functionNotExists('buildRelationItem')) {
		builder.buildRelationItem = function(relation) {
			return $('<li></li>')
				.addClass('cur-relations__item')
				.attr('data-typestate-id', relation.typestate_id)
				.append(
					$('<span></span>')
						.text(relation.type_name + ' – ' + relation.state_name)
						.addClass('name'),
				)
				.append(this.buildRemove())
				.append(this.buildHiddenFields(relation));
		};
	}

	/**
	 * build user relations list
	 * @param relations
	 * @return {element}
	 */
	if (functionNotExists('buildRelationsList')) {
		builder.buildRelationsList = function(relations) {
			const $ul = $(`<ul></ul>`).addClass('cur-relations_list');
			if (typeof relations !== typeof undefined) {
				for (let i = 0; i < relations.length; i++) {
					this.buildRelationItem(relations[i]).appendTo($ul);
				}
			}
			return $ul;
		};

	}

	/**
	 * build user table row
	 * @param user
	 * @return {element}
	 */
	if (functionNotExists('buildRow')) {
		builder.buildRow = function(user) {
			const name = user.display_name;
			const $row = $(`
<tr>
	<td class="name column-name">
		<a href="${builder.getUserProfileLink(
				user.user_id)}" target="_blank">${name}</a>
	</td>
	<td class="relations column-relations"></td>
</tr>`)
				.attr('id', 'cur-user-row-' + user.user_id)
				.data('user', user)
				.addClass('cur-user_row');


			$row.find('.relations')
				.append(this.buildRelationsList(user.relations));

			return $row;
		};
	}

	/**
	 * build row for empty table visualization
	 * @return {element}
	 */
	if (functionNotExists('buildEmptyRow')) {
		builder.buildEmptyRow = function() {
			return $(
				`<tr class="no-items"><td class="colspanchange" colspan="3">${i18n.no_relations_found}</td></tr>`);
		};
	}

	/**
	 * build the main table for relations
	 * @return {element}
	 */
	if (functionNotExists('buildTable')) {
		builder.buildTable = function() {
			return $(`<table class="wp-list-table widefat fixed striped relatedmembers">
            <thead>
	            <tr>
	                <th scope="col">${i18n.col_title_user}</th><th scope="col">${i18n.col_title_relations}</th>           
	            </tr>
            </thead>
            <tbody></tbody>
        </table>`);
		};
	}

	/**
	 *
	 * @param typestates
	 * @return {element}
	 */
	if (functionNotExists('buildRelationTypeSelect')) {
		builder.buildRelationTypeSelect = function(typestates) {
			const $wrapper = $(
				`<label class='cur-relation-type-label' for='cur-state-type-select'>${i18n.label_typestate_select}: 
<select id='cur-state-type-select'></select></label>`);
			const $select = $wrapper.find('select');
			for (let i = 0; i < typestates.length; i++) {
				const typestate = typestates[i];
				$('<option></option>')
					.text(`${typestate.type_name} – ${typestate.state_name}`)
					.attr('value', typestate.id)
					.data('typestate', typestate)
					.appendTo($select);
			}
			return $wrapper;
		};
	}

	/**
	 *
	 * @return {element}
	 */
	if (functionNotExists('buildAutocompleteControl')) {
		builder.buildAutocompleteControl = function() {
			return $(`
<label for="cur-autocomplete">${i18n.label_autocomplete_users}: 
<input type="text" id="cur-autocomplete" name="cur_user_autocomplete" />
</label>`);
		};
	}

	/**
	 * @param typestates
	 * @return {element}
	 */
	if (functionNotExists('buildControls')) {
		builder.buildControls = function(typestates) {
			return $('<div></div>')
				.addClass('cur-controls')
				.append(this.buildRelationTypeSelect(typestates)
					.addClass('cur-control'))
				.append(
					this.buildAutocompleteControl().addClass('cur-control'));
		};
	}

	if (functionNotExists('buildAutocompleteUserItem')) {
		builder.buildAutocompleteUserItem = function(user) {
			const $li = $('<li></li>').addClass('cur-autocomplete-user-item');
			$li.append(
				$('<div></div>')
					.text(user.display_name)
					.addClass('user-item__user-name'),
			);
			$li.append(
				$('<div></div>')
					.text('EMail: ' + user.user_email)
					.addClass('user-item__user-email'),
			);
			$li.append(
				$('<div></div>')
					.text('ID: ' + user.ID)
					.addClass('user-item__ID'),
			);
			$li.data('item.data', user);
			return $li;
		};
	}

	// ----------------------------
	// pure functions
	// ----------------------------
	if (functionNotExists('findActionInput')) {
		builder.findActionInput = function($element) {
			return $element.find(`input[name^="${POST.actions}"]`);
		};
	}

	if (functionNotExists('findRelations')) {
		builder.findRelations = function findRelations($element) {
			return $element.find('.cur-relations_list');
		};
	}

	if (functionNotExists('findRelationsByTypestateId')) {
		builder.findRelationsByTypestateId = function($element, typestate_id) {
			return $element.find(`[data-typestate-id=${typestate_id}]`);
		};
	}

	if (functionNotExists('findUserRow')) {
		builder.findUserRow = function(user_id) {
			return this.elements.$tbody.find(`#cur-user-row-${user_id}`);
		};
	}

	if (functionNotExists('getUserProfileLink')) {
		builder.getUserProfileLink = function(user_id) {
			return links.user_profile.replace('%uid%', user_id);
		};
	}

	if (functionNotExists('')) {
		builder.getSelectedTypeState = function($controls) {
			return $controls.find('select')
				.children(':selected')
				.data('typestate');
		};
	}

	// ----------------------------
	// event handlers
	// ----------------------------

	if (typeof builder.events === typeof undefined) {
		builder.events = {};
	}
	const events = builder.events;

	if (functionNotExists('on_remove', events)) {
		events.on_remove = function(e) {
			e.preventDefault();
			const $btn = $(this);
			const $relation_row = $btn.closest('li');
			const user = $relation_row.closest("tr").data("user");

			if ($relation_row.hasClass('will-be-added')) {
				autocomplete_cache_invalidate_for(user);
				if ($relation_row.siblings().length < 1) {
					$relation_row.closest('tr').remove();
					builder.checkEmptyTable();
					builder.update_parent_modification_state($relation_row);
				}
				else {
					const $parent = $relation_row.parent();
					$relation_row.remove();
					builder.update_parent_modification_state($parent);
				}
				$(window.document.body)
					.trigger(HOOKS.USER_RELATION_REMOVE, user);
				return;
			}

			$relation_row.toggleClass('will-be-removed');
			const $action = builder.findActionInput($relation_row);
			if ($relation_row.hasClass('will-be-removed')) {
				$action.val(ACTION.remove);
				$btn.text(i18n.unremove);
			}
			else {
				$btn.text(i18n.remove);
				$action.val('');
			}

			builder.update_parent_modification_state($relation_row);
			$(window.document.body)
				.trigger(HOOKS.USER_RELATION_REMOVE, user);

		};
	}

	if (functionNotExists('addUserRelation', events)) {
		events.addUserRelation = function(user, typeStateItem) {
			builder.elements.$app.trigger(
				'content_user_relations_add_relation',
				[user, typeStateItem],
			);
		};
	}

	if (functionNotExists('on_add_user_relation', events)) {
		events.on_add_user_relation = function(e, user, relation) {
			let $user_row = builder.findUserRow(user.ID);
			relation.typestate_id = relation.id;
			relation.user_id = user.ID;
			user.user_id = user.ID;
			if ($user_row.length < 1) {
				// add new row
				user.relations = [relation];
				$user_row = builder.buildRow(user);
				builder.findActionInput($user_row).val(ACTION.add);
				builder.findRelations($user_row)
					.children()
					.last()
					.addClass('will-be-added');
				$user_row.addClass('will-be-added');
				builder.elements.$tbody.append($user_row);
				builder.elements.$emptyRow.remove();
				return;
			}
			else if ($user_row.length > 1) {
				console.error('Too many rows for user');
			}

			const $item = builder.findRelationsByTypestateId(
				$user_row,
				relation.typestate_id,
			);
			if ($item.length > 0) {
				alert('Relation already exists');
				return;
			}
			const $relations_list = builder.findRelations($user_row);
			const $relation = builder.buildRelationItem(relation)
				.addClass('will-be-added');
			builder.findActionInput($relation).val(ACTION.add);
			$relations_list.append($relation);

			builder.update_parent_modification_state($relations_list);

		};
	}

	if (functionNotExists('update_parent_modification_state')) {
		builder.update_parent_modification_state = function($childElement) {
			// user row visualization
			const $user_row = $childElement.closest('tr');
			if ($user_row.find(
				'.cur-relations__item.will-be-removed').length ===
				$user_row.find('.cur-relations__item').length) {
				$user_row.addClass('will-be-removed');
			}
			else {
				$user_row.removeClass('will-be-removed');
			}
		};
	}

	const autocomplete_cache = {};

	function autocomplete_cache_get(term) {
		return (typeof autocomplete_cache[term] !== typeof undefined) ?
			autocomplete_cache[term] :
			null;
	}

	function autocomplete_cache_add_term(term, user_list) {
		autocomplete_cache[term] = user_list;
	}

	function autocomplete_cache_remove_user(user) {
		for (let term in autocomplete_cache) {
			if (!autocomplete_cache.hasOwnProperty(term)) {
				continue;
			}
			autocomplete_cache[term] = autocomplete_cache[term].filter(
				(u) => u.ID !== user.ID,
			);
		}
	}

	function autocomplete_cache_invalidate_for(user) {
		for (let term in autocomplete_cache) {
			if (!autocomplete_cache.hasOwnProperty(term)) {
				continue;
			}
			if(user.display_name.indexOf(term) !== false || user.user_email.indexOf(term) !== false){
				autocomplete_cache[term] = undefined;
				delete autocomplete_cache[term];
			}
		}
	}

	if (functionNotExists('initAutocomplete', events)) {
		events.initAutocomplete = function($controls) {
			const $autocomplete = $controls.find('input').first();
			$autocomplete.autocomplete({
				source: function(request, response) {
					const term = request.term;
					const cache = autocomplete_cache_get(request.term);
					if (cache) {
						response(cache);
						return;
					}
					api.findRelatableUsers(request.term, function(data) {
						autocomplete_cache_add_term(request.term, data.users);
						response(data.users);
					}, {post_id: post_id});
				},
				select: function(event, ui) {
					builder.events.addUserRelation(
						ui.item,
						builder.getSelectedTypeState($controls),
					);
					autocomplete_cache_remove_user(ui.item);
					$(window.document.body).trigger(HOOKS.USER_RELATION_ADD);
					return false;
				},
				delay: 500,
				minLength: 3,
			});

			$autocomplete.on('click', function() {
				$autocomplete.autocomplete('instance')
					.search($autocomplete.val());
			});

			$autocomplete.autocomplete('instance')._renderItem = function(
				ul, item) {
				return builder.buildAutocompleteUserItem(item).appendTo(ul);
			};

		};
	}

	if (functionNotExists('checkEmptyTable')) {
		builder.checkEmptyTable = function() {
			if (this.elements.$tbody.html() === '') {
				this.elements.$tbody.append(this.elements.$emptyRow);
			}
		};
	}

	// ----------------------------
	// init application
	// ----------------------------

	if (functionNotExists('init')) {
		builder.init = function() {

			this.elements = {};
			this.elements.$app = $(`#${this.app_root_id}`);

			this.elements.$app.on('click', 'a.remove',
				builder.events.on_remove);
			this.elements.$app.on('content_user_relations_add_relation',
				builder.events.on_add_user_relation);

			this.elements.$table = builder.buildTable();
			this.elements.$app.append(this.elements.$table);

			this.elements.$tbody = this.elements.$table.find('tbody');
			this.elements.$emptyRow = builder.buildEmptyRow();
			for (let i = 0; i < relations.length; i++) {
				builder.buildRow(relations[i]).appendTo(this.elements.$tbody);
			}

			builder.checkEmptyTable();
			this.elements.$controls = builder.buildControls(typestates);
			this.elements.$app.append($('<h3></h3>')
				.text(i18n.label_add_user_control)
				.addClass('cur-controls__label'));
			this.elements.$app.append(this.elements.$controls);

			this.events.initAutocomplete(this.elements.$controls);

			// only save values if javascript has successfully saved state
			this.elements.$app.append(
				builder.buildHiddenField(POST.ready_to_save,
					ready_to_save_value));

			if (typeof this.events.on_ready === typeof []) {
				for (let i = 0; i < this.events.on_ready.length; i++) {
					if (typeof this.events.on_ready[i] ===
						typeof 'function') {
						this.events.on_ready[i]();
					}
				}
			}
		};
	}

	// lets get it started
	builder.init();
	$(window.document.body).trigger(HOOKS.READY);

})(
	jQuery,
	ContentUserRelations_API,
	ContentUserRelations_MetaBox,
	window.ContentUserRelations_MetaBox_Builder || {});