// userInfo may or may not be defined, depending on if the user is logged in or not
import { actions } from '../init';


const reducer = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.userInfo ? action.userInfo : {};
	}
	return state;
};


export {
	reducer
};
