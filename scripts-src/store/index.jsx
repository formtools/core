import thunk from 'react-thunk';
import { createStore, applyMiddleware } from 'redux';


const reducers = {};


function initStore (initialState) {
	console.log('confirm only called once...');

	const createStoreWithMiddleware = applyMiddleware(thunk)(createStore);
	return createStoreWithMiddleware(combineReducers(reducers), initialState);
}

const store = initStore({

	// every page that contains any React code needs things like the i18n, constants etc. loaded. This section
	// is populate by init.jsx in the parent folder. All top-level connected components in the page call init.
	initialized: false,
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
});

export default store;
