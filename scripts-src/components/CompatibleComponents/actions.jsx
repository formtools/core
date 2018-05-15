import thunk from 'redux-thunk';


// actions
const DOWNLOAD_COMPATIBLE_COMPONENTS = 'DOWNLOAD_COMPATIBLE_COMPONENTS';
const COMPATIBLE_COMPONENTS_LOADED = 'COMPATIBLE_COMPONENTS_LOADED';
const COMPATIBLE_COMPONENTS_LOAD_ERROR = 'COMPATIBLE_COMPONENTS_LOAD_ERROR';

// action creators
const getCompatibleComponents = () => {

	// do async request here
//	return (dispatch, getState) => {
//		const { counter } = getState();
//
//		if (counter % 2 === 0) {
//			return;
//		}
//
//		dispatch(increment());
//	};

	return {
		type: GET_COMPATIBLE_COMPONENT_LIST
	};
};


export {
	DOWNLOAD_COMPATIBLE_COMPONENTS,
	COMPATIBLE_COMPONENTS_LOADED,
	COMPATIBLE_COMPONENTS_LOAD_ERROR,
	getCompatibleComponents
};
