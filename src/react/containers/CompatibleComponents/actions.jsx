import * as selectors from './selectors';
import * as generalSelectors from '../../core/selectors';
import * as helpers from './helpers';
import store from '../../core/store';

export const COMPATIBLE_COMPONENTS_LOADED = 'COMPATIBLE_COMPONENTS_LOADED';


export const getCompatibleComponents = () => {
	return function (dispatch, getState) {
		const state = getState();

		const base_url = state.constants.data_source_url;
		const core_version = state.constants.core_version;

		fetch(`${base_url}/feeds/core/${core_version}.json`)
			.then((response) => response.json())
			.then((json) => {
				dispatch({
					type: COMPATIBLE_COMPONENTS_LOADED,
					api: json.api,
					modules: json.modules,
					themes: json.themes,
                    default_components: json.default_components
				});
			}).catch((e) => dispatch(compatibleComponentsLoadError(e)))
	};
};

export const COMPATIBLE_COMPONENTS_LOAD_ERROR = 'COMPATIBLE_COMPONENTS_LOAD_ERROR';
export const compatibleComponentsLoadError = () => ({ type: COMPATIBLE_COMPONENTS_LOAD_ERROR });

export const TOGGLE_API = 'TOGGLE_API';
export const TOGGLE_MODULE = 'TOGGLE_MODULE';
export const TOGGLE_THEME = 'TOGGLE_THEME';
const toggleAPI = () => ({ type: TOGGLE_API });
const toggleModule = (folder) => ({ type: TOGGLE_MODULE, folder });
const toggleTheme = (folder) => ({ type: TOGGLE_THEME, folder });

export const toggleComponent = (componentTypeSection, folder) => {
    if (componentTypeSection === 'modules') {
        return toggleModule(folder);
    } else if (componentTypeSection === 'themes') {
        return toggleTheme(folder);
    } else {
        return toggleAPI();
    }
};

export const EDIT_SELECTED_COMPONENT_LIST = 'EDIT_SELECTED_COMPONENT_LIST';
export const editSelectedComponentList = () => ({ type: EDIT_SELECTED_COMPONENT_LIST });

export const SAVE_SELECTED_COMPONENT_LIST = 'SAVE_SELECTED_COMPONENT_LIST';
export const saveSelectedComponentList = () => ({ type: SAVE_SELECTED_COMPONENT_LIST });

export const CANCEL_EDIT_SELECTED_COMPONENT_LIST = 'CANCEL_EDIT_SELECTED_COMPONENT_LIST';
export const cancelEditSelectedComponentList = () => ({ type: CANCEL_EDIT_SELECTED_COMPONENT_LIST });

export const SELECT_COMPONENT_TYPE_SECTION = 'SELECT_COMPONENT_TYPE_SECTION';
export const selectComponentTypeSection = (section) => ({
    type: 'SELECT_COMPONENT_TYPE_SECTION',
    section
});

export const SELECT_ALL_MODULES = 'SELECT_ALL_MODULES';
export const DESELECT_ALL_MODULES = 'DESELECT_ALL_MODULES';
export const toggleAllModulesSelected = () => {
    return (dispatch, getState) => {
        const allSelected = selectors.allModulesSelected(getState());
        dispatch({
            type: allSelected ? DESELECT_ALL_MODULES : SELECT_ALL_MODULES
        });
    };
};

export const SHOW_COMPONENT_CHANGELOG_MODAL = 'SHOW_COMPONENT_CHANGELOG_MODAL';

// folder is the theme/module folder, or "core" or "api"
export const showComponentInfo = ({ componentType, folder }) => {
    return (dispatch, getState) => {
        const changelogs = selectors.getChangelogs(getState());

        if (!changelogs.hasOwnProperty(folder)) {
            queryComponentInfo(componentType, folder);
        }

        dispatch({
            type: SHOW_COMPONENT_CHANGELOG_MODAL,
            payload: {
                componentType,
                folder
            }
        });
    }
};


// pings the server to get the component history for the Core, API, module or theme
export const COMPONENT_HISTORY_LOADED = 'COMPONENT_HISTORY_LOADED';
const queryComponentInfo = (componentType, folder) => {
    const url = `../global/code/actions-react.php?action=get_component_info&type=${componentType}&component=${folder}`;

    fetch(url)
        .then((response) => response.json())
        .then((json) => {
            store.dispatch({
                type: COMPONENT_HISTORY_LOADED,
                payload: {
                    folder,
                    desc: json.hasOwnProperty('desc') ? json.desc : null,
                    versions: json.versions
                }
            });
        }).catch((e) => {
            // TODO
            // store.dispatch({
            //     type: INIT_DATA_ERROR_LOADING,
            //     error: e
            // });
        });
};


export const CLOSE_COMPONENT_CHANGELOG_MODAL = 'CLOSE_COMPONENT_CHANGELOG_MODAL';
export const closeComponentInfo = () => ({ type: CLOSE_COMPONENT_CHANGELOG_MODAL });


export const onPrevNext = (dir) => {
    return (dispatch, getState) => {
        const prevNext = selectors.getPrevNextComponent(getState());

        if ((dir === 'prev' && prevNext.prev === null) ||
            (dir === 'next' && prevNext.next === null)) {
            return;
        }

        if (dir === 'prev') {
            dispatch(showComponentInfo({ ...prevNext.prev }));
        } else {
            dispatch(showComponentInfo({ ...prevNext.next }));
        }
    };
};


export const START_DOWNLOAD_COMPATIBLE_COMPONENTS = 'START_DOWNLOAD_COMPATIBLE_COMPONENTS';
export const downloadCompatibleComponents = () => {

	return (dispatch, getState) => {
		const state = getState();
		const selectedComponents = selectors.getSelectedComponents(state);
		const constants = generalSelectors.getConstants(state);

		let componentList = {};
		selectedComponents.forEach((item) => {
			if (item.type === 'core') {
				return;
			}
			componentList[helpers.getComponentIdentifier(item.folder, item.type)] = { downloaded: false, log: [] };
		});

		dispatch({
			type: START_DOWNLOAD_COMPATIBLE_COMPONENTS,
			payload: { componentList }
		});

		selectedComponents.forEach((item) => {
			if (item.type === 'core') {
				return;
			}
			downloadAndUnpackComponent(item, constants.data_source_url);
		});
	};
};


export const COMPONENT_DOWNLOAD_UNPACK_RESPONSE = 'COMPONENT_DOWNLOAD_UNPACK_RESPONSE'; // TODO rename... SUCCESS/ERROR ?
const downloadAndUnpackComponent = (item, data_source_url) => {
	let zipfile_url = '';
	if (item.type === 'api') {
		zipfile_url = `${data_source_url}/api/${item.version}.zip`;
	} else if (item.type === 'module') {
		zipfile_url = `${data_source_url}/modules/${item.folder}-${item.version}.zip`;
	} else if (item.type === 'theme') {
		zipfile_url = `${data_source_url}/themes/${item.folder}-${item.version}.zip`;
	}
	let cleanUrl = encodeURIComponent(zipfile_url);

	const actions_url = `../global/code/actions-react.php?action=installation_download_single_component&type=${item.type}&url=${cleanUrl}`;

	fetch(actions_url)
		.then((response) => response.json())
		.then((json) => {
			store.dispatch({
				type: COMPONENT_DOWNLOAD_UNPACK_RESPONSE,
				payload: {
					...json,
					folder: item.folder,
					type: item.type
				}
			});
		}).catch((e) => {
		// store.dispatch({
		//     type: INIT_DATA_ERROR_LOADING,
		//     error: e
		// });
		});
};













