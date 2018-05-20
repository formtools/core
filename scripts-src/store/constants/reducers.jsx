
import * as actions from "../init/actions";

export const constants = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADING) {
		return {
			root_url: constants.root_url,
			root_dir: constants.root_dir,
			data_source_url: constants.data_source_url,
			core_version: constants.core_version
		};
	}
	return state;
};
