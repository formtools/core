export const DOWNLOAD_COMPATIBLE_COMPONENTS = 'DOWNLOAD_COMPATIBLE_COMPONENTS';
export const downloadCompatibleComponents = () => ({ type: DOWNLOAD_COMPATIBLE_COMPONENTS });

export const COMPATIBLE_COMPONENTS_LOADED = 'COMPATIBLE_COMPONENTS_LOADED';


export const getCompatibleComponents = () => {
	return function (dispatch, getState) {
		const state = getState();

		const base_url = state.constants.data_source_url;
		const core_version = state.constants.core_version;

		fetch(`${base_url}/core-${core_version}.json`)
			.then((response) => response.json())
			.then((json) => {
				dispatch({
					type: COMPATIBLE_COMPONENTS_LOADED,
					api: json.api,
					modules: json.modules,
					themes: json.themes
				});
			}).catch((e) => dispatch(compatibleComponentsLoadError(e)))
	};
};

export const COMPATIBLE_COMPONENTS_LOAD_ERROR = 'COMPATIBLE_COMPONENTS_LOAD_ERROR';
export const compatibleComponentsLoadError = () => ({ type: COMPATIBLE_COMPONENTS_LOAD_ERROR });

export const UPDATE_SEARCH_FILTER = 'UPDATE_SEARCH_FILTER';
export const updateSearchFilter = (str) => ({
	type: UPDATE_SEARCH_FILTER,
	searchFilter: str
});

export const TOGGLE_API = 'TOGGLE_API';
export const toggleAPI = () => ({ type: TOGGLE_API });

export const TOGGLE_MODULE = 'TOGGLE_MODULE';
export const toggleModule = (folder) => {
	return {
		type: TOGGLE_MODULE,
		folder
	}
};

export const TOGGLE_THEME = 'TOGGLE_THEME';
export const toggleTheme = (folder) => ({
	type: TOGGLE_THEME,
	folder
});

export const EDIT_SELECTED_COMPONENT_LIST = 'EDIT_SELECTED_COMPONENT_LIST';
export const editSelectedComponentList = () => ({ type: EDIT_SELECTED_COMPONENT_LIST });

export const SAVE_SELECTED_COMPONENT_LIST = 'SAVE_SELECTED_COMPONENT_LIST';
export const saveSelectedComponentList = () => ({ type: SAVE_SELECTED_COMPONENT_LIST });

export const CANCEL_EDIT_SELECTED_COMPONENT_LIST = 'CANCEL_EDIT_SELECTED_COMPONENT_LIST';
export const cancelEditSelectedComponentList = () => ({ type: CANCEL_EDIT_SELECTED_COMPONENT_LIST });
