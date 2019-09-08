import reducerRegistry from '../../store/reducerRegistry';
import * as actions from './installation.actions';
import { actions as initActions } from '../../store/init';

const reducer = (state = {
	language: 'en_us',
	loading: false,
	systemCheckResults: null,
	useCustomCacheFolder: false,
	customCacheFolder: '',

	// config.php values
	dbHostname: '',
	dbName: '',
	dbPort: '',
	dbUsername: '',
	dbPassword: '',
	dbTablePrefix: ''
}, action) => {
	switch (action.type) {
		case actions.START_REQUEST: {
			return {
				...state,
				loading: true
			};
		}
		case actions.REQUEST_RETURNED: {
			return {
				...state,
				loading: false
			};
		}
		case actions.SYSTEM_CHECK_DATA_RETURNED: {
			return {
				...state,
				loading: false,
				systemCheckResults: { ...action.payload },
				customCacheFolder: action.payload.customCacheFolder
			};
		}
		case initActions.INIT_DATA_LOADED: {
			return {
				...state,
				language: action.payload.language
			};
		}
		case initActions.LANGUAGE_UPDATED: {
			return {
				...state,
				language: action.payload.language
			};
		}
		case actions.TOGGLE_CUSTOM_CACHE_FOLDER: {
			return {
				...state,
				useCustomCacheFolder: !state.useCustomCacheFolder
			};
		}
		case actions.UPDATE_CUSTOM_CACHE_FOLDER: {
			return {
				...state,
				customCacheFolder: action.payload.value
			};
		}

		case actions.UPDATE_DATABASE_FIELD: {
			const { field, value } = action.payload;
			return {
				...state,
				[field]: value
			};
		}
	}
	return state;
};

reducerRegistry.register('installation', reducer);
