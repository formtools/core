import reducerRegistry from '../reducerRegistry';
import { actions } from '../init';


const reducer = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.payload.i18n;
	}
	return state;
};

reducerRegistry.register('i18n', reducer);
