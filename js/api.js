"use strict";

(function($, api){

	/**
	 *
	 * @param {string} search
	 * @param {object|null|undefined} data
	 */
	function buildData(search, data){
		const result = {};
		if(typeof data === "object"){
			const keys = Object.keys(data);
			for(let i = 0; i < keys.length; i++){
				result[keys[i]] = data[keys[i]];
			}
		}
		result.s = search;
		return result;
	}

	/**
	 *
	 * @param {string} search
	 * @param {function} cb
	 * @param data null|object
	 */
	api.findRelatableContents = function(search, cb, data){

		$.ajax({
			url: api.ajaxurls.findContents,
			data: buildData(search, data),
			success: function(result){
				cb(result);
			}
		});
	};

	/**
	 *
	 * @param search
	 * @param cb
	 * @param data null|object
	 */
	api.findRelatableUsers = function(search, cb, data) {
		$.ajax({
			url: api.ajaxurls.findUsers,
			data: buildData(search, data),
			success: function(result){
				cb(result);
			}
		});
	}

})(jQuery, ContentUserRelations_API);