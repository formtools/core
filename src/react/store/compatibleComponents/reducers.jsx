import { actions } from './actions';
import * as helpers from './helpers';
import { convertHashToArray, removeFromArray } from '../../helpers';


export default function reducer (state = {
	loaded: false,
	errorLoading: false,
	error: '',
	isEditing: false,
    showComponentInfoModal: false,
    componentInfoModalContent: '', // { type: 'module' | theme | api | core, folder: '' }
    componentChangelog: {}, // component_folder => { versions: [], isLoading: true|false }
	core: {},
	api: {},
	modules: {},
	themes: {},
    changelogs: {}, // populated on demand when a user clicks the About link for a component
    selectedComponentTypeSection: 'modules',
    selectedModuleFolders: [],
    selectedThemeFolders: [],
	apiSelected: false,

	// Downloading
	isDownloading: false,
	downloadComplete: false,
	showDetailedDownloadLog: false,

	// populated the moment the user proceeds to the download step. This is a 1-level deep object of property names:
	// api, theme_[theme folder], module_[module folder] with each value containing an object of
	// { downloaded: false|true, log: [] }
	downloadedComponents: {},

    // any time the user clicks "Customize" we stash the last config here, in case they cancel their changes and
    // want to revert
    lastSavedComponents: {}
}, action) {

	switch (action.type) {

		// converts the list of modules and themes to an object with (unique) folder names as keys
        case actions.COMPATIBLE_COMPONENTS_LOADED:
			const modules = {};

			action.modules.forEach(({ name, desc, folder, repo, version }) => {
				modules[folder] = {
					name, desc, folder, repo,
					version: version.version,
                    type: 'module'
				};
			});

			const themes = {};
			action.themes.forEach(({ name, desc, folder, repo, version }) => {
				themes[folder] = {
					name, desc, folder, repo,
					version: version.version,
                    type: 'theme'
				};
			});

            let api = {};
            if (action.api.length) {
                api = {
                    name: 'API',
                    folder: 'api',
                    type: 'api',
                    version: action.api[0].version,
                    release_date: action.api[0].release_date
                };
            }

            // only preselect modules and themes that ARE in fact in the available module/theme list
            const preselected_modules = action.default_components.modules.filter((module) => modules.hasOwnProperty(module));
            const preselected_themes = action.default_components.themes.filter((theme) => themes.hasOwnProperty(theme));

			return Object.assign({}, state, {
				loaded: true,
				modules,
				themes,
                api,
                apiSelected: action.default_components.api,
                selectedModuleFolders: preselected_modules,
				selectedThemeFolders: preselected_themes
			});

		case actions.TOGGLE_API:
            return {
                ...state,
                apiSelected: !state.apiSelected
            };

		case actions.TOGGLE_MODULE:
			return {
				...state,
				selectedModuleFolders: selectedComponentsReducer(state.selectedModuleFolders, action.folder)
			};

		case actions.TOGGLE_THEME:
			return {
				...state,
				selectedThemeFolders: selectedComponentsReducer(state.selectedThemeFolders, action.folder)
			};

        case actions.SELECT_ALL_MODULES:
            return {
                ...state,
                selectedModuleFolders: convertHashToArray(state.modules).map((module) => module.folder)
            };

        case actions.DESELECT_ALL_MODULES:
            return {
                ...state,
                selectedModuleFolders: []
            };

		case actions.EDIT_SELECTED_COMPONENT_LIST:
			return {
				...state,
				isEditing: true,
                lastSavedComponents: {
				    selectedModuleFolders: state.selectedModuleFolders,
                    selectedThemeFolders: state.selectedThemeFolders,
                    apiSelected: state.apiSelected
                }
			};

		case actions.CANCEL_EDIT_SELECTED_COMPONENT_LIST:
			return {
				...state,
				isEditing: false,
                selectedModuleFolders: state.lastSavedComponents.selectedModuleFolders,
                selectedThemeFolders: state.lastSavedComponents.selectedThemeFolders,
                apiSelected: state.lastSavedComponents.apiSelected
			};

		case actions.START_DOWNLOAD_COMPATIBLE_COMPONENTS:
			return {
				...state,
				isDownloading: true,
				downloadedComponents: action.payload.componentList
			};

		case actions.COMPONENT_DOWNLOAD_UNPACK_RESPONSE:
			const downloadedComponents = { ...state.downloadedComponents };
			const componentId = helpers.getComponentIdentifier(action.payload.folder, action.payload.type);
			downloadedComponents[componentId].downloadSuccess = action.payload.success;
			downloadedComponents[componentId].log = action.payload.log;

			// if all components are downloaded, flag the downloaded process as complete
			let allDownloaded = Object.keys(downloadedComponents).every((component) => {
				return downloadedComponents[component].downloadSuccess !== null;
			});

			// TODO need to add another flag here asking if the whole process was success or not

			return {
				...state,
				downloadedComponents,
				isDownloading: !allDownloaded,
				downloadComplete: allDownloaded
			};

        case actions.SAVE_SELECTED_COMPONENT_LIST:
            return {
                ...state,
                isEditing: false
            };

        case actions.SELECT_COMPONENT_TYPE_SECTION:
            return {
                ...state,
                selectedComponentTypeSection: action.section
            };

        case actions.SHOW_COMPONENT_CHANGELOG_MODAL:
            return {
                ...state,
                showComponentInfoModal: true,
                componentInfoModalContent: {
                    componentType: action.payload.componentType,
                    folder: action.payload.folder
                }
            };

        case actions.CLOSE_COMPONENT_CHANGELOG_MODAL:
            return {
                ...state,
                showComponentInfoModal: false
            };

        case actions.COMPONENT_HISTORY_LOADED:
            const updatedChangelogs = { ...state.changelogs };
            updatedChangelogs[action.payload.folder] = action.payload.versions;

            const newState = {
                ...state,
                changelogs: updatedChangelogs
            };

            if (action.payload.folder === 'core') {
                newState.core = { desc: action.payload.desc };
            } else if (action.payload.folder === 'api') {
                newState.api.desc = action.payload.desc;
            }
            return newState;

		case actions.TOGGLE_SHOW_DETAILED_DOWNLOAD_LOG:
			return {
				...state,
				showDetailedDownloadLog: !state.showDetailedDownloadLog
			};
	}


	return state;
};

const selectedComponentsReducer = (state = [], folder) => {
    if (state.includes(folder)) {
        return removeFromArray(state, folder);
    } else {
        return [...state, folder];
    }
};
