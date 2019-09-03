import reducerRegistry from '../reducerRegistry';
import * as actions from './init.actions';

const reducer = (state = {
	initialized: false,
	errorInitializing: false,
	isAuthenticated: false,
	availableLanguages: []
}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		const { isAuthenticated, availableLanguages } = action.payload;
		return Object.assign({}, state, {
			initialized: true,
			isAuthenticated,
			availableLanguages
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

reducerRegistry.register('init', reducer);
