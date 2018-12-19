"use strict";

(function($, api){

	const HOOKS = api.HOOKS = {
		ON_FIND_CONTENTS_ARGS: "find_contents_args",
		ON_FIND_USERS_ARGS: "find_users_args",
	};

	const _hooks = {};

	const safeHook = (name)=>{
		if(typeof _hooks[name] !== typeof []){
			_hooks[name] = [];
		}
		return _hooks[name];
	};

	api.onHook = function(name, hook) {
		safeHook(name).push(hook);
	};

	api.fireHook = function(name, data) {
		for( let fn of safeHook(name)){
			data = fn(data);
		}
		return data;
	};

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
			method: "POST",
			url: api.ajaxurls.findContents,
			data: api.fireHook(HOOKS.ON_FIND_CONTENTS_ARGS,buildData(search, data)),
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
			method: "POST",
			url: api.ajaxurls.findUsers,
			data: api.fireHook(HOOKS.ON_FIND_USERS_ARGS,buildData(search, data)),
			success: function(result){
				cb(result);
			}
		});
	}

})(jQuery, ContentUserRelations_API);