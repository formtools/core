import reducerRegistry from '../../store/reducerRegistry';
import * as actions from './installation.actions';
import { actions as initActions } from '../../store/init';

const reducer = (state = {
	language: 'en_us',
	loading: false
}, action) => {
	switch (action.type) {
		case actions.START_REQUEST: {
			return {
				...state,
				loading: true
			};
		}
		case actions.SYSTEM_CHECK_DATA_RETURNED: {
			return {
				...state,
				loading: false
			};
		}
		case initActions.INIT_DATA_LOADED: {
			return {
				...state,
				language: action.payload.language
			};
		}
		case initActions.LANGUAGE_UPDATED: {
			return {
				...state,
				language: action.payload.language
			};
		}
	}
	return state;
};

reducerRegistry.register('installation', reducer);
