import { actions } from '../init';

const reducer = (state = {
	root_url: null,
	root_dir: null,
	data_source_url: null,
	core_version: null
}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return {
			root_url: action.constants.root_url,
			root_dir: action.constants.root_dir,
			data_source_url: action.constants.data_source_url,
			core_version: action.constants.core_version
		};
	}
	return state;
};


const selectors = {
	getConstants: (state) => state.constants
};

export {
	reducer,
	selectors
};

