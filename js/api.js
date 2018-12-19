'use strict';

(function($, api) {

	// ------------------
	// hooks
	// ------------------
	const HOOKS = api.HOOKS = {
		ON_FIND_CONTENTS_ARGS: 'find_contents_args',
		ON_FIND_USERS_ARGS: 'find_users_args',
	};

	const _hooks = {};

	const safeHook = (name) => {
		if (typeof _hooks[name] !== typeof []) {
			_hooks[name] = [];
		}
		return _hooks[name];
	};

	api.onHook = function(name, hook) {
		safeHook(name).push(hook);
	};

	api.fireHook = function(name, data) {
		for (let fn of safeHook(name)) {
			data = fn(data);
		}
		return data;
	};

	// ------------------
	// cache
	// ------------------
	const setup_cache = () => {
		const cache = {};

		const get = (key) => cache[key];
		const set = (key, value) => {
			cache[key] = value;
		};

		const exists = key => typeof cache[key] !== typeof undefined;

		const keys = ()=> Object.keys(cache);

		const invalidate = (key_or_function) => {
			if (typeof key_or_function === 'function') {
				for (let key in cache) {
					if (!cache.hasOwnProperty(key)) {
						continue;
					}
					if (key_or_function(key, cache[key])) {
						invalidate(key);
					}
				}
			}
			else if (typeof cache[key_or_function] !== typeof undefined) {
				cache[key_or_function] = undefined;
				delete cache[key_or_function];
			}
		};

		return {
			get,
			set,
			keys,
			exists,
			invalidate,
		};
	};

	const caches = {};
	api.cache = function(name) {
		if (typeof caches[name] === typeof undefined) {
			caches[name] = setup_cache();
		}
		return caches[name];
	};

	/**
	 *
	 * @param {string} search
	 * @param {object|null|undefined} data
	 */
	function buildData(search, data) {
		const result = {};
		if (typeof data === 'object') {
			const keys = Object.keys(data);
			for (let i = 0; i < keys.length; i++) {
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
	api.findRelatableContents = function(search, cb, data) {
		$.ajax({
			method: 'POST',
			url: api.ajaxurls.findContents,
			data: api.fireHook(HOOKS.ON_FIND_CONTENTS_ARGS,
				buildData(search, data)),
			success: function(result) {
				cb(result);
			},
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
			method: 'POST',
			url: api.ajaxurls.findUsers,
			data: api.fireHook(HOOKS.ON_FIND_USERS_ARGS,
				buildData(search, data)),
			success: function(result) {
				cb(result);
			},
		});
	};

})(jQuery, ContentUserRelations_API);