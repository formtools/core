import thunk from 'redux-thunk';

export const DOWNLOAD_COMPATIBLE_COMPONENTS = 'DOWNLOAD_COMPATIBLE_COMPONENTS';
export const downloadCompatibleComponents = () => ({ type: DOWNLOAD_COMPATIBLE_COMPONENTS });

export const COMPATIBLE_COMPONENTS_LOADED = 'COMPATIBLE_COMPONENTS_LOADED';
export const getCompatibleComponents = () => {
	return function (dispatch, getState) {
		// construct URL from constants pulled from getState

		fetch('http://localhost:8888/formtools-site/formtools.org/feeds/source/core-3.1.0.json')
			.then((response) => response.json())
			.then((json) => {
				dispatch({
					type: COMPATIBLE_COMPONENTS_LOADED,
					api: json.api,
					modules: json.modules,
					themes: json.themes
				});
			}).catch(() => dispatch(compatibleComponentsLoadError()))
	};
};

export const COMPATIBLE_COMPONENTS_LOAD_ERROR = 'COMPATIBLE_COMPONENTS_LOAD_ERROR';
export const compatibleComponentsLoadError = () => ({ type: COMPATIBLE_COMPONENTS_LOAD_ERROR });

export const UPDATE_SEARCH_FILTER = 'UPDATE_SEARCH_FILTER';
export const updateSearchFilter = (str) => ({
	type: UPDATE_SEARCH_FILTER,
	searchFilter: str
});
