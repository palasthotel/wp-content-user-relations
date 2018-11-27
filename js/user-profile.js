(function($, api, data){

	var table_id = "cur-table";
	var autocomplete_id = "cur-content-autocomplete";
	var statetype_hidden_id = "cur-new-relation-content-id";

	$(function(){
		var $table = $("#"+table_id);
		var $autocomplete = $("#"+autocomplete_id);
		var $hidden = $("#"+statetype_hidden_id);

		$autocomplete.autocomplete({
			source: function(request, response){
				api.findRelatableContents(request.term,function(data){
					response(data);
				});
			},
			focus: function(event, ui){
				$autocomplete.val(ui.item.post_title);
				$hidden.val(ui.item.ID);
				return false;
			},
			select: function(event, ui){
				$hidden.val(ui.item.ID);
				$autocomplete.val(ui.item.post_title);
				return false;
			},
			change:function(event, ui){
				console.log(ui);
				if(ui == null || ui.item == null){
					$hidden.val("");
				}
			},
			delay: 500,
			minLength: 3,
		});

		$autocomplete.autocomplete("instance")._renderItem = function(ul, item){
			var $li = $("<li></li>");
			$li.data("item.data", item);
			$li.text(item.post_title);
			return $li.appendTo(ul);
		};

		$table.on("click",".cur-states__item", function(){
			const $item = $(this);
			$item.toggleClass("is-deleted");

			if($item.hasClass("is-deleted")){
				buildMeta($item).appendTo($item);
			} else {
				$item.find(".meta").remove();
			}

		});

	});

	function buildMeta($item){

		// data
		var content_id = $item.attr("data-post-id");
		var type_slug = $item.attr("data-type-slug");
		var state_slug = $item.attr("data-state-slug");

		var $wrapper = $("<span/>").addClass("meta");
		$("<input/>")
		.attr("type","hidden")
		.attr("name", "cur_delete_relation_content[]")
		.val(content_id)
		.appendTo($wrapper);

		$("<input/>")
		.attr("type","hidden")
		.attr("name", "cur_delete_relation_type[]")
		.val(type_slug)
		.appendTo($wrapper);

		$("<input/>")
		.attr("type","hidden")
		.attr("name", "cur_delete_relation_state[]")
		.val(state_slug)
		.appendTo($wrapper);

		return $wrapper;
	}


})(jQuery, ContentUserRelations_API, ContentUserRelations_Profile);