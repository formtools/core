import reducerRegistry from '../reducerRegistry';
import { actions } from '../init';


const reducer = (state = {}, action) => {
	switch (action.type) {
		case actions.INIT_DATA_LOADED:
			return action.payload.i18n;
		case actions.LANGUAGE_UPDATED:
			return action.payload.i18n;
	}
	return state;
};

reducerRegistry.register('i18n', reducer);
