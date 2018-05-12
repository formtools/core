import thunk from 'redux-thunk';

// actions
const DOWNLOAD_COMPATIBLE_COMPONENTS = 'DOWNLOAD_COMPATIBLE_COMPONENTS';
const COMPATIBLE_COMPONENTS_LOADED = 'COMPATIBLE_COMPONENTS_LOADED';
const COMPATIBLE_COMPONENTS_LOAD_ERROR = 'COMPATIBLE_COMPONENTS_LOAD_ERROR';

// action creators
const compatibleComponentsLoaded = ({ api, modules, themes }) => ({
	type: COMPATIBLE_COMPONENTS_LOADED,
	api,
	modules,
	themes
});

const compatibleComponentsLoadError = () => ({ type: COMPATIBLE_COMPONENTS_LOAD_ERROR });
const downloadCompatibleComponents = () => ({ type: DOWNLOAD_COMPATIBLE_COMPONENTS });

const getCompatibleComponents = () => {
	return function (dispatch, getState) {

		// construct URL from constants pulled from getState

		fetch('http://localhost:8888/formtools-site/formtools.org/feeds/source/core-3.1.0.json')
			.then((response) => response.json())
			.then((json) => {
				dispatch(compatibleComponentsLoaded({
					type: COMPATIBLE_COMPONENTS_LOADED,
					api: json.api,
					modules: json.modules,
					themes: json.themes
				}));
			}).catch(() => dispatch(compatibleComponentsLoadError()))
	};
};

export {
	DOWNLOAD_COMPATIBLE_COMPONENTS,
	COMPATIBLE_COMPONENTS_LOADED,
	COMPATIBLE_COMPONENTS_LOAD_ERROR,
	compatibleComponentsLoaded,
	compatibleComponentsLoadError,
	getCompatibleComponents,
	downloadCompatibleComponents
};
