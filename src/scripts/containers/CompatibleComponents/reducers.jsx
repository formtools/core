import * as actions from './actions';
import { convertHashToArray, removeFromArray } from '../../core/helpers';

export default (state = {
	loaded: false,
	errorLoading: false,
	error: '',
	isEditing: false,
	core: {},
	api: {},
	modules: {},
	themes: {},
    selectedComponentTypeSection: 'modules',
    selectedModuleFolders: [],
    selectedThemeFolders: [],
    apiSelected: false,

    // any time the user clicks "Customize" we stash the last config here, in case they cancel their changes and
    // want to revert
    lastSavedComponents: {}
}, action) => {

	switch (action.type) {

		// converts the list of modules and themes to an object with (unique) folder names as keys
        case actions.COMPATIBLE_COMPONENTS_LOADED:
			const modules = {};

			action.modules.forEach(({ name, desc, folder, repo, version }) => {
				modules[folder] = {
					name, desc, folder, repo,
					version: version.version
				};
			});

			const themes = {};
			action.themes.forEach(({ name, desc, folder, repo, version }) => {
				themes[folder] = {
					name, desc, folder, repo,
					version: version.version
				};
			});

            let api = {};
            if (action.api.length) {
                api = {
                    ...action.api[0],
                    name: 'API',
                    folder: 'api'
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