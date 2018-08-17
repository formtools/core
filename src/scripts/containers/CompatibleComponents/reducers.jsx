import * as actions from './actions';
import C from '../../core/constants';
import { removeFromArray } from '../../core/helpers';

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
    selectedThemeFolders: []
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

			return Object.assign({}, state, {
				loaded: true,
				modules,
				themes,
				selectedModuleFolders: C.PRESELECTED_MODULES,
				selectedThemeFolders: C.PRESELECTED_THEMES
			});

		case actions.TOGGLE_API:
			return Object.assign({}, state, {
				api: Object.assign({}, state.api, {
					selected: !state.api.selected
				})
			});

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

		case actions.EDIT_SELECTED_COMPONENT_LIST:
			return {
				...state,
				isEditing: true
			};

		case actions.CANCEL_EDIT_SELECTED_COMPONENT_LIST:
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




