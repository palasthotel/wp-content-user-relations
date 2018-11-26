"use strict";

(function($, api){

	/**
	 *
	 * @param {string} search
	 * @param {function} cb
	 */
	api.findRelatableContents = function(search, cb){
		$.ajax({
			url: api.ajaxurls.findContents,
			data: {
				s:search,
			},
			success: function(result){
				cb(result);
			}
		});
	};

	api.findRelatableUsers = function(search, cb) {
		$.ajax({
			url: api.ajaxurls.findUsers,
			data: {
				s:search,
			},
			success: function(result){
				cb(result);
			}
		});
	}

})(jQuery, ContentUserRelations_API);