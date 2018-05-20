import * as actions from "../init/actions";

export const userInfo = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.userInfo;
	}
	return state;
};
