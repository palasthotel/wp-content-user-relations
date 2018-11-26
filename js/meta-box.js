(function($, api, data) {

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
				$('<div></div>').text(user.display_name+" → "+typeStateItem.name)
			).append(
				$('<input/>')
				.attr("name", new_type_state_id_name+"[]")
				.attr("type", "hidden")
				.val(typeStateItem.id),
			).append(
				$('<input/>')
				.attr("type", "hidden")
				.attr("name", new_user_id_name+"[]")
				.val(user.ID),
			)
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
		return $li
	}

})(jQuery, ContentUserRelations_API, ContentUserRelations_MetaBox);