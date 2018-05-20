import * as actions from './actions';


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
