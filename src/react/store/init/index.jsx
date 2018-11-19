import store from "../index";


const actions = {
	INIT_DATA_LOADED: 'INIT_DATA_LOADED',
	INIT_DATA_ERROR_LOADING: 'INIT_DATA_ERROR_LOADING'
};


const reducer = (state = {
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


export const getInitializationData = () => {
	fetch('../global/code/actions-react.php?action=init')
		.then((response) => response.json())
		.then((json) => {
			store.dispatch({
				type: actions.INIT_DATA_LOADED,
				...json
			});
		}).catch((e) => {
		store.dispatch({
			type: actions.INIT_DATA_ERROR_LOADING,
			error: e
		});
	});
};


const actionCreators = {
	getInitializationData
};


const selectors = {
	getInitialized: (state) => state.init.initialized
} ;


export {
	reducer,
	actions,
	actionCreators,
	selectors
};
