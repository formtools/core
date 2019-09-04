import reducerRegistry from '../../store/reducerRegistry';
import { actions as initActions } from '../../store/init';

const reducer = (state = {
	language: 'en_us'
}, action) => {
	switch (action.type) {
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
