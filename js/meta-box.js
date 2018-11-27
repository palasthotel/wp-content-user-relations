(function($, api, data) {

	// ----------------------------
	// injected vars
	// ----------------------------
	const POST = data.POST;
	const ACTION = data.ACTION;
	const ready_to_save_value = data.ready_to_save_value;
	const $app = $(`#${data.app_root_id}`);

	// ----------------------------
	// relations table builder
	// ----------------------------

	/**
	 * build a single hidden field
	 * @param name
	 * @param value
	 * @return {element}
	 */
	function buildHiddenField(name, value) {
		return $("<input />")
		.val(value)
		.attr("name", name)
		.attr("type", "hidden");
	}

	/**
	 * build hidden fields for post_save action
	 * @param relation
	 * @param action
	 * @return [element]
	 */
	function buildHiddenFields(relation, action = "") {
		return [
			buildHiddenField(`${POST.user_ids}[]`, relation.user_id),
			buildHiddenField(`${POST.typestate_ids}[]`, relation.typestate_id),
			buildHiddenField(`${POST.actions}[]`, ""),
		]
	}

	/**
	 * build a delete button
	 * @return {element}
	 */
	function buildDelete() {
		return $("<button></button>").text("Delete").addClass("delete");
	}

	/**
	 * build user relation item row
	 * @param relation
	 * @return {element}
	 */
	function buildRelationItem(relation) {
		return $("<li></li>")
			.addClass("cur-relations__item")
			.append(
				$("<span></span>")
				.text(relation.type_name+" – "+relation.state_name)
				.addClass("name")
			)
			.append(buildDelete())
			.append(buildHiddenFields(relation));
	}

	/**
	 * build user relations list
	 * @param relations
	 * @return {element}
	 */
	function buildRelationsList(relations) {
		const $ul = $(`<ul></ul>`).addClass('cur-relations_list');
		for(let i = 0; i < relations.length; i++){
			buildRelationItem(relations[i]).appendTo($ul);
		}
		return $ul;
	}

	/**
	 * build user table row
	 * @param user
	 * @return {element}
	 */
	function buildRow(user) {
		const name = user.display_name;
		const $row = $(`
<tr>
	<td class="name column-name" data-colname="User">${name}</td>
	<td class="relations column-relations" data-colname="Relations"></td>
</tr>`).addClass("cur-user_row");

		$row.find(".relations").append(buildRelationsList(user.relations));

		return $row;
	}

	/**
	 * build row for empty table visualization
	 * @return {element}
	 */
	function buildEmptyRow() {
		return $(`<tr class="no-items"><td class="colspanchange" colspan="3">Keine Elemente gefunden.</td></tr>`)
	}

	/**
	 * build the main table for relations
	 * @return {element}
	 */
	function buildTable() {
		return $(`<table class="wp-list-table widefat fixed striped relatedmembers">
            <thead>
	            <tr>
	                <th scope="col">User</th><th scope="col">Relations</th>           
	            </tr>
            </thead>
            <tbody></tbody>
        </table>`);
	}

	// ----------------------------
	// event handlers
	// ----------------------------
	$app.on("click", "button.delete", function(e){
		e.preventDefault();
		const $btn = $(this);
		const $relation_row = $btn.closest("li");
		$relation_row.toggleClass("will-be-deleted");
		const $action = $relation_row.find(`input[name^="${POST.actions}"]`);
		if($relation_row.hasClass("will-be-deleted")){
			$action.val(ACTION.delete);
			$btn.text("dont delete");
		} else {
			$btn.text("delete");
			$action.val("");
		}

		// user row visualization
		const $user_row = $relation_row.closest("tr");
		if($user_row.find('.cur-relations__item.will-be-deleted').length === $user_row.find('.cur-relations__item').length ){
			$user_row.addClass("will-be-deleted");
		} else {
			$user_row.removeClass("will-be-deleted");
		}

	});

	// ----------------------------
	// init application
	// ----------------------------
	const $table = buildTable();
	$app.append($table);
	const $tbody = $table.find("tbody");
	for( let i = 0; i < data.relations.length; i++){
		buildRow(data.relations[i]).appendTo($tbody);
	}

	// only save values if javascript has successfully saved state
	$app.append(buildHiddenField(POST.ready_to_save, ready_to_save_value));


	// ----------------------------
	// @deprecated section
	// ----------------------------
	const $root = $('#' + data.root_id);
	const $stateTypeSelect = $root.find('select');
	const $autocomplete = $root.find(
		'input[name=' + data.autocomplete_input_name + ']');
	const $list = $root.find('ul');

	const new_user_id_name = data.name_user_id_arr;
	const new_type_state_id_name = data.name_type_state_id_arr;

	$autocomplete.autocomplete({
		source: function(request, response) {
			api.findRelatableUsers(request.term, function(data) {
				response(data.users);
			});
		},
		select: function(event, ui) {
			addUserRelation(ui.item, getTypeStateItem());
			return false;
		},
		delay: 500,
		minLength: 3,
	});

	$autocomplete.autocomplete('instance')._renderItem = function(ul, item) {
		return buildAutocompleteItem(item).appendTo(ul);
	};

	// get typestate relation information out of select
	function getTypeStateItem() {
		console.log($stateTypeSelect.children(':selected'));
		return {
			id: $stateTypeSelect.val(),
			name: $stateTypeSelect.children(':selected').text(),
		};
	}

	// add relation to new relations list
	function addUserRelation(user, typeStateItem) {
		console.log(user, typeStateItem);
		$list.append(
			$('<li></li>').append(
				$('<div></div>').
					text(user.display_name + ' → ' + typeStateItem.name),
			).append(
				$('<input/>').
					attr('name', new_type_state_id_name + '[]').
					attr('type', 'hidden').
					val(typeStateItem.id),
			).append(
				$('<input/>').
					attr('type', 'hidden').
					attr('name', new_user_id_name + '[]').
					val(user.ID),
			),
		);
	}

	function buildAutocompleteItem(user) {
		const $li = $('<li></li>').addClass('cur-autocomplete-user-item');
		$li.append(
			$('<div></div>').
				text(user.display_name).
				addClass('user-item__user-name'),
		);
		// $li.append(
		// 	$("<div></div>")
		// 	.text(item.user_email)
		// 	.addClass("user-item__user-email")
		// );
		$li.append(
			$('<div></div>').text('ID: ' + user.ID).addClass('user-item__ID'),
		);
		$li.data('item.data', user);
		return $li;
	}

})(jQuery, ContentUserRelations_API, ContentUserRelations_MetaBox);