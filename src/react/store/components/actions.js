import { selectors as constantSelectors } from '../../store/constants';
import * as helpers from './helpers';
import * as selectors from './selectors';
import store from '../../store';


export const actions = {
	SET_CORE_VERSION: 'SET_CORE_VERSION',
	COMPATIBLE_COMPONENTS_LOADED: 'COMPATIBLE_COMPONENTS_LOADED',
	COMPATIBLE_COMPONENTS_LOAD_ERROR: 'COMPATIBLE_COMPONENTS_LOAD_ERROR',
	TOGGLE_API: 'TOGGLE_API',
	TOGGLE_MODULE: 'TOGGLE_MODULE',
	TOGGLE_THEME: 'TOGGLE_THEME',
	EDIT_SELECTED_COMPONENT_LIST: 'EDIT_SELECTED_COMPONENT_LIST',
	SAVE_SELECTED_COMPONENT_LIST: 'SAVE_SELECTED_COMPONENT_LIST',
	CANCEL_EDIT_SELECTED_COMPONENT_LIST: 'CANCEL_EDIT_SELECTED_COMPONENT_LIST',
	SELECT_COMPONENT_TYPE_SECTION: 'SELECT_COMPONENT_TYPE_SECTION',
	SELECT_ALL_MODULES: 'SELECT_ALL_MODULES',
	DESELECT_ALL_MODULES: 'DESELECT_ALL_MODULES',
	SHOW_COMPONENT_CHANGELOG_MODAL: 'SHOW_COMPONENT_CHANGELOG_MODAL',
	COMPONENT_HISTORY_LOADED: 'COMPONENT_HISTORY_LOADED',
	CLOSE_COMPONENT_CHANGELOG_MODAL: 'CLOSE_COMPONENT_CHANGELOG_MODAL',
	START_DOWNLOAD_COMPATIBLE_COMPONENTS: 'START_DOWNLOAD_COMPATIBLE_COMPONENTS',
	COMPONENT_DOWNLOAD_UNPACK_RESPONSE: 'COMPONENT_DOWNLOAD_UNPACK_RESPONSE', // TODO rename: SUCCESS/ERROR ?
	TOGGLE_SHOW_DETAILED_DOWNLOAD_LOG: 'TOGGLE_SHOW_DETAILED_DOWNLOAD_LOG',

	INSTALLED_COMPONENTS_LOADED: 'INSTALLED_COMPONENTS_LOADED',
	INSTALLED_COMPONENTS_ERROR_LOADING: 'INSTALLED_COMPONENTS_ERROR_LOADING'
};


/**
 * Gets the full list of compatible components for a particular core version.
 * @return {Function}
 */
const getCompatibleComponents = () => {
	return function (dispatch, getState) {
		const state = getState();
		const base_url = state.constants.data_source_url;
		const core_version = state.constants.core_version;

		dispatch(setCoreVersion(core_version));

		fetch(`${base_url}/feeds/core/${core_version}.json`)
			.then((response) => response.json())
			.then((json) => {
				dispatch({
					type: actions.COMPATIBLE_COMPONENTS_LOADED,
					payload: {
						coreVersion: core_version, // TODO convert everything to camel
						api: json.api,
						modules: json.modules,
						themes: json.themes,
						default_components: json.default_components
					}
				});
			}).catch((e) => {
				dispatch(compatibleComponentsLoadError(e));
			});
	};
};

const compatibleComponentsLoadError = () => ({ type: actions.COMPATIBLE_COMPONENTS_LOAD_ERROR });
const toggleAPI = () => ({ type: actions.TOGGLE_API });
const toggleModule = (folder) => ({ type: actions.TOGGLE_MODULE, folder });
const toggleTheme = (folder) => ({ type: actions.TOGGLE_THEME, folder });
const setCoreVersion = (coreVersion) => ({ type: actions.SET_CORE_VERSION, payload: { coreVersion }});

const toggleComponent = (componentTypeSection, folder) => {
    if (componentTypeSection === 'modules') {
        return toggleModule(folder);
    } else if (componentTypeSection === 'themes') {
        return toggleTheme(folder);
    } else {
        return toggleAPI();
    }
};

const editSelectedComponentList = () => ({ type: actions.EDIT_SELECTED_COMPONENT_LIST });

const saveSelectedComponentList = () => ({ type: actions.SAVE_SELECTED_COMPONENT_LIST });

const cancelEditSelectedComponentList = () => ({ type: actions.CANCEL_EDIT_SELECTED_COMPONENT_LIST });

const selectComponentTypeSection = (section) => ({
    type: actions.SELECT_COMPONENT_TYPE_SECTION,
    section
});

const toggleAllModulesSelected = () => {
    return (dispatch, getState) => {
        const allSelected = selectors.allModulesSelected(getState());
        dispatch({
            type: allSelected ? actions.DESELECT_ALL_MODULES : actions.SELECT_ALL_MODULES
        });
    };
};


// folder is the theme/module folder, or "core" or "api"
const showComponentInfo = ({ componentType, folder }) => {
    return (dispatch, getState) => {
        const changelogs = selectors.getChangelogs(getState());

        if (!changelogs.hasOwnProperty(folder)) {
            queryComponentInfo(componentType, folder);
        }

        dispatch({
            type: actions.SHOW_COMPONENT_CHANGELOG_MODAL,
            payload: {
                componentType,
                folder
            }
        });
    }
};


// pings the server to get the component history for the Core, API, module or theme
const queryComponentInfo = (componentType, folder) => {
    const url = `../global/code/actions-react.php?action=get_component_info&type=${componentType}&component=${folder}`;

    fetch(url)
        .then((response) => response.json())
        .then((json) => {
            store.dispatch({
                type: actions.COMPONENT_HISTORY_LOADED,
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

const closeComponentInfo = () => ({ type: actions.CLOSE_COMPONENT_CHANGELOG_MODAL });


const onPrevNext = (dir) => {
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


const downloadCompatibleComponents = () => {
	return (dispatch, getState) => {
		const state = getState();
		const selectedComponents = selectors.getSelectedComponents(state);
		const constants = constantSelectors.getConstants(state);

		let componentList = {};
		selectedComponents.forEach((item) => {
			if (item.type === 'core') {
				return;
			}
			componentList[helpers.getComponentIdentifier(item.folder, item.type)] = {
				downloadSuccess: null, // set to true/false depending on whether the component was successfully updated
				log: []
			};
		});

		dispatch({
			type: actions.START_DOWNLOAD_COMPATIBLE_COMPONENTS,
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
				type: actions.COMPONENT_DOWNLOAD_UNPACK_RESPONSE,
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


const toggleShowDetailedDownloadLog = () => ({ type: actions.TOGGLE_SHOW_DETAILED_DOWNLOAD_LOG });


const getInstalledComponents = () => {
	fetch(`${g.root_url}/global/code/actions-react.php?action=get_installed_components`)
		.then((response) => response.json())
		.then((json) => {
			store.dispatch({
				type: actions.INSTALLED_COMPONENTS_LOADED,
				payload: {
					components: json
				}
			});
		}).catch((e) => {
			store.dispatch({
				type: actions.INSTALLED_MODULES_ERROR_LOADING, // TODO
				error: e
			});
		});
};

export const actionCreators = {
	setCoreVersion,
	getCompatibleComponents,
	//compatibleComponentsLoadError,
	toggleComponent,
	editSelectedComponentList,
	saveSelectedComponentList,
	cancelEditSelectedComponentList,
	selectComponentTypeSection,
	toggleAllModulesSelected,
	showComponentInfo,
	closeComponentInfo,
	onPrevNext,
	downloadCompatibleComponents,
	toggleShowDetailedDownloadLog,
	getInstalledComponents
};
