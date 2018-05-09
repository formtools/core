import thunk from 'thunk';
import fetch from 'fetch';


// actions
const DOWNLOAD_COMPATIBLE_COMPONENTS = 'DOWNLOAD_COMPATIBLE_COMPONENTS';
const COMPATIBLE_COMPONENTS_LOADED = 'COMPATIBLE_COMPONENTS_LOADED';
const COMPATIBLE_COMPONENTS_LOAD_ERROR = 'COMPATIBLE_COMPONENTS_LOAD_ERROR';

// action creators
const getCompatibleComponents = () => {

	// do async request here

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
