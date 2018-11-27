(function($, api, data) {

	// ----------------------------
	// injected vars
	// ----------------------------
	const i18n = data.i18n;
	const relations = data.relations;
	const typestates = data.typestates;
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
	 * build a remove button
	 * @return {element}
	 */
	function buildRemove() {
		return $("<button></button>").text(i18n.remove).addClass("remove");
	}

	/**
	 * build user relation item row
	 * @param relation
	 * @return {element}
	 */
	function buildRelationItem(relation) {
		return $("<li></li>")
			.addClass("cur-relations__item")
			.attr("data-typestate-id", relation.typestate_id)
			.append(
				$("<span></span>")
				.text(relation.type_name+" – "+relation.state_name)
				.addClass("name")
			)
			.append(buildRemove())
			.append(buildHiddenFields(relation));
	}

	/**
	 * build user relations list
	 * @param relations
	 * @return {element}
	 */
	function buildRelationsList(relations) {
		const $ul = $(`<ul></ul>`).addClass('cur-relations_list');
		if(typeof relations !== typeof undefined){
			for(let i = 0; i < relations.length; i++){
				buildRelationItem(relations[i]).appendTo($ul);
			}
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
	<td class="name column-name">${name}</td>
	<td class="relations column-relations"></td>
</tr>`)
		.attr("id", "cur-user-row-"+user.user_id)
		.addClass("cur-user_row");

		$row.find(".relations").append(buildRelationsList(user.relations));

		return $row;
	}

	/**
	 * build row for empty table visualization
	 * @return {element}
	 */
	function buildEmptyRow() {
		return $(`<tr class="no-items"><td class="colspanchange" colspan="3">${i18n.no_relations_found}</td></tr>`)
	}

	/**
	 * build the main table for relations
	 * @return {element}
	 */
	function buildTable() {
		return $(`<table class="wp-list-table widefat fixed striped relatedmembers">
            <thead>
	            <tr>
	                <th scope="col">${i18n.col_title_user}</th><th scope="col">${i18n.col_title_relations}</th>           
	            </tr>
            </thead>
            <tbody></tbody>
        </table>`);
	}

	/**
	 *
	 * @param typestates
	 * @return {element}
	 */
	function buildRelationTypeSelect(typestates) {
		const $wrapper = $(`<label class='cur-relation-type-label' for='cur-state-type-select'>${i18n.label_typestate_select}: 
<select id='cur-state-type-select'></select></label>`);
		const $select = $wrapper.find("select");
		for(let i = 0; i < typestates.length; i++){
			const typestate = typestates[i];
			$("<option></option>")
				.text(`${typestate.type_name} – ${typestate.state_name}`)
				.attr("value", typestate.id)
				.data("typestate", typestate)
				.appendTo($select);
		}
		return $wrapper;
	}

	/**
	 *
	 * @return {element}
	 */
	function buildAutocompleteControl(){
		return $(`<label for="cur-autocomplete">${i18n.label_autocomplete_users}: <input type="text" id="cur-autocomplete" name="cur_user_autocomplete" /></label>`)
	}

	// ----------------------------
	// pure functions
	// ----------------------------
	function findActionInput($element){
		return $element.find(`input[name^="${POST.actions}"]`);
	}
	function findRelations($element){
		return $element.find('.cur-relations_list');
	}
	function findRelationsByTypestateId($element, typestate_id){
		return $element.find(`[data-typestate-id=${typestate_id}]`);
	}
	function findUserRow(user_id){
		return $tbody.find(`#cur-user-row-${user_id}`);
	}

	// ----------------------------
	// event handlers
	// ----------------------------
	$app.on("click", "button.remove", function(e){
		e.preventDefault();
		const $btn = $(this);
		const $relation_row = $btn.closest("li");

		if($relation_row.hasClass("will-be-added")){
			if($relation_row.siblings().length < 1){
				$relation_row.closest("tr").remove();
				checkEmptyTable();
				update_parent_modification_state($relation_row);
			} else {
				const $parent = $relation_row.parent();
				$relation_row.remove();
				update_parent_modification_state($parent);
			}
			return;
		}

		$relation_row.toggleClass("will-be-removed");
		const $action = findActionInput($relation_row);
		if($relation_row.hasClass("will-be-removed")){
			$action.val(ACTION.delete);
			$btn.text(i18n.unremove);
		} else {
			$btn.text(i18n.remove);
			$action.val("");
		}

		update_parent_modification_state($relation_row);

	});

	$app.on("content_user_relations_add_relation", function(e, user, relation){
		let $user_row = findUserRow(user.ID);
		relation.typestate_id = relation.id;
		relation.user_id = user.ID;
		if($user_row.length < 1){
			// add new row
			user.relations = [relation];
			$user_row = buildRow(user);
			findActionInput($user_row).val(ACTION.add);
			findRelations($user_row).children().last().addClass("will-be-added");
			$user_row.addClass("will-be-added");
			$tbody.append($user_row);
			$emptyRow.remove();
			return;
		} else if($user_row.length > 1){
			console.error("Too many rows for user");
		}

		const $item = findRelationsByTypestateId($user_row, relation.typestate_id);
		if($item.length > 0){
			alert("Relation already exists");
			return;
		}
		const $relations_list = findRelations($user_row);
		// TODO: add relation
		const $relation = buildRelationItem(relation)
		.addClass("will-be-added");
		findActionInput($relation).val(ACTION.add);
		$relations_list.append($relation);

		update_parent_modification_state($relations_list);

	});

	function update_parent_modification_state($childElement) {
		// user row visualization
		const $user_row = $childElement.closest("tr");
		if($user_row.find('.cur-relations__item.will-be-removed').length === $user_row.find('.cur-relations__item').length ){
			$user_row.addClass("will-be-removed");
		} else {
			$user_row.removeClass("will-be-removed");
		}
	}

	function initAutocomplete($autocomplete, $stateTypeSelect) {

		if(!$autocomplete.is("input")) $autocomplete = $autocomplete.find("input").first();

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
			return $stateTypeSelect.children(':selected').data("typestate")
		}

		// add relation to new relations list
		function addUserRelation(user, typeStateItem) {
			$app.trigger("content_user_relations_add_relation", [ user, typeStateItem]);
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

	}

	function checkEmptyTable() {
		if($tbody.html() === ""){
			$tbody.append($emptyRow);
		}
	}

	// ----------------------------
	// init application
	// ----------------------------
	const $table = buildTable();
	$app.append($table);
	const $tbody = $table.find("tbody");
	const $emptyRow = buildEmptyRow();
	for( let i = 0; i < relations.length; i++){
		buildRow(relations[i]).appendTo($tbody);
	}

	checkEmptyTable();

	// only save values if javascript has successfully saved state
	$app.append(buildHiddenField(POST.ready_to_save, ready_to_save_value));

	const $typestateselectwrapper = buildRelationTypeSelect(typestates);
	$app.append($typestateselectwrapper);

	const $autocomplete_wrapper = buildAutocompleteControl();
	$app.append($autocomplete_wrapper);
	initAutocomplete($autocomplete_wrapper, $typestateselectwrapper.find("select"));


	// ----------------------------
	// @deprecated section
	// ----------------------------




})(jQuery, ContentUserRelations_API, ContentUserRelations_MetaBox);