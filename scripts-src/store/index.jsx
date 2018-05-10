import { createStore, applyMiddleware } from 'redux';

// data structure
/*{
	initialized: false, // see init.jsx
	errorInitializing: false,
	isAuthenticated: false,
	userInfo: {},
	i18n: {},
	constants: {},

	// used for installation + upgrades. This contains all compatible component versions for the user's current
	// Core version
	compatibleComponents: {
		loaded: false,
		errorLoading: false,
		error: '',
		core: {},
		api: {},
		modules: [],
		themes: []
	},

	// in case the user's core version is out of date, this second location contains the list of components that
	// are compatible with the latest version of the core. This info is automatically downloaded via a second
	// request if there's a new core available
	latestCoreCompatibleComponents: {
		loaded: false,
		errorLoading: false,
		error: '',
		core: {},
		api: {},
		modules: [],
		themes: []
	},

	// separate request per component, loaded on-demand
	componentChangelogs: {}
}*/
