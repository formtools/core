import axios from 'axios';
import { actions as initActions } from '../../store/init';
import { navUtils } from '../../utils';
import { selectors } from '../store';

export const START_REQUEST = 'START_REQUEST';
export const REQUEST_ERROR = 'REQUEST_ERROR';
export const REQUEST_RETURNED = 'REQUEST_RETURNED';

export const startRequest = () => ({ type: START_REQUEST });
export const requestReturned = () => ({ type: REQUEST_RETURNED });

export const TOGGLE_CUSTOM_CACHE_FOLDER = 'TOGGLE_CUSTOM_CACHE_FOLDER';
export const toggleCustomCacheFolder = () => ({ type: TOGGLE_CUSTOM_CACHE_FOLDER });

export const UPDATE_CUSTOM_CACHE_FOLDER = 'UPDATE_CUSTOM_CACHE_FOLDER';
export const updateCustomCacheFolder = (value) => ({
	type: UPDATE_CUSTOM_CACHE_FOLDER,
	payload: {
		value
	}
});

export const UPDATE_DATABASE_FIELD = 'UPDATE_DATABASE_FIELD';
export const updateDatabaseField = (field, value) => ({
	type: UPDATE_DATABASE_FIELD,
	payload: {
		field,
		value
	}
});

export const saveCacheFolderSetting = (onSuccess, onError) => {
	return (dispatch, getState) => {
		const state = getState();
		dispatch(startRequest());

		const payload = new FormData();
		payload.append('action', 'saveCacheFolderSettings');
		payload.append('useCustomCacheFolder', selectors.shouldUseCustomCacheFolder(state));
		payload.append('customCacheFolder', selectors.getCustomCacheFolder(state));

		axios.post('./actions-installation.php', payload)
			.then(() => {
				dispatch(requestReturned());
				onSuccess();
			})
			.catch((e) => {
				dispatch(requestReturned());
				onError(e.response.data.error);
			});
	};
};

export const saveDbSettings = (onSuccess, onError) => {
	return (dispatch, getState) => {
		const state = getState();
		dispatch(startRequest());

		const payload = new FormData();
		payload.append('action', 'saveDbSettings');
		payload.append('dbHostname', selectors.getDbHostname(state));
		payload.append('dbName', selectors.getDbName(state));
		payload.append('dbPort', selectors.getDbPort(state));
		payload.append('dbUsername', selectors.getDbUsername(state));
		payload.append('dbPassword', selectors.getDbPassword(state));
		payload.append('dbTablePrefix', selectors.getDbTablePrefix(state));

		axios.post('./actions-installation.php', payload)
			.then(() => {
				dispatch(requestReturned());
				onSuccess();
			})
			.catch((e) => {
				console.log(e.response);
				dispatch(requestReturned());
				onError(e.response.data);
			});
	};
};

export const restartInstallation = (history) => {
	return (dispatch) => {
		dispatch(initActions.clearGlobalError());
		if (navUtils.getCurrentInstallationPage() !== 1) {
			history.push('/');
		}
	};
};
