import * as actions from './actions';

export const constants = (state = {
	root_url: null,
	root_dir: null,
	data_source_url: null,
	core_version: null
}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return {
			root_url: action.constants.root_url,
			root_dir: action.constants.root_dir,
			data_source_url: action.constants.data_source_url,
			core_version: action.constants.core_version
		};
	}
	return state;
};

// userInfo may or may not be defined, depending on if the user is logged in or not
export const userInfo = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.userInfo ? action.userInfo : {};
	}
	return state;
};

export const i18n = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.i18n;
	}
	return state;
};

export const init = (state = {
	initialized: false,
	errorInitializing: false,
	isAuthenticated: false
}, action) => {

	if (action.type === actions.INIT_DATA_LOADED) {
		return Object.assign({}, state, {
			initialized: true,
			isAuthenticated: actions.is_logged_in
		});
	} else if (action.type === actions.INIT_DATA_ERROR_LOADING) {
		return Object.assign({}, state, {
			initialized: true,
			errorInitializing: false
		});
	} else {
		return state;
	}
};

export const downloads = (state = {
	isDownloading: false,
	core: null,
	api: null,
	modules: null,
	themes: null
}, action) => {

	return state;
};
