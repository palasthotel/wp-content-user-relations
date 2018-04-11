"use strict";

(function($, api){

	/**
	 *
	 * @param {string} search
	 * @param {function} cb
	 */
	api.findRelatableContents = function(search, cb){
		$.ajax({
			url: api.ajaxurls.find,
			data: {
				s:search,
			},
			success: function(result){
				cb(result);
			}
		});
	}



})(jQuery, ContentUserRelations_API);