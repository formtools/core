import * as actions from '../init/actions';

export const i18n = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.i18n;
	}
	return state;
};
