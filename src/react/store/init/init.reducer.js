import reducerRegistry from '../reducerRegistry';
import * as actions from './init.actions';

const reducer = (state = {
	initialized: false,
	errorInitializing: false,
	isAuthenticated: false,
	globalError: '',
	availableLanguages: []
}, action) => {
	switch (action.type) {
		case actions.INIT_DATA_LOADED: {
			const { isAuthenticated, availableLanguages } = action.payload;
			return {
				...state,
				initialized: true,
				isAuthenticated,
				availableLanguages
			};
		}
		case actions.INIT_DATA_ERROR_LOADING:
			return {
				...state,
				initialized: true,
				errorInitializing: false
			};
		case actions.SET_GLOBAL_ERROR:
			return {
				...state,
				globalError: action.payload.globalError
			};
		default:
			return state;
	}
};

reducerRegistry.register('init', reducer);
