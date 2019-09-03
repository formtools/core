import reducerRegistry from '../../store/reducerRegistry';
import * as actions from './installation.actions';

const reducer = (state = {
	language: 'en_us'
}, action) => {
	switch (action.type) {
		case actions.SELECT_LANGUAGE: {
			return {
				...state,
				language: action.payload.language
			};
		}
	}
	return state;
};

reducerRegistry.register('installation', reducer);
