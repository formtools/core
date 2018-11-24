import { actions } from '../init';


const reducer = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.i18n;
	}
	return state;
};

const selectors = {
	getI18n: (state) => state.i18n
};

export {
	reducer,
	selectors
};
