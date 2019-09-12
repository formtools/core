import axios from 'axios';
import { actions as initActions } from '../../store/init';
import { navUtils } from '../../utils';
import { selectors } from '../store';

export const SYSTEM_CHECK_DATA_RETURNED = 'SYSTEM_CHECK_DATA_RETURNED';
export const START_REQUEST = 'START_REQUEST';
export const REQUEST_ERROR = 'REQUEST_ERROR';
export const REQUEST_RETURNED = 'REQUEST_RETURNED';

export const startRequest = () => ({ type: START_REQUEST });
export const requestReturned = () => ({ type: REQUEST_RETURNED });

export const getSystemCheckResults = () => {
	return (dispatch) => {
		dispatch(startRequest());

		axios.get(`./actions-installation.php?action=getSystemCheckResults`)
			.then(({ data }) => {
				dispatch({
					type: SYSTEM_CHECK_DATA_RETURNED,
					payload: {
						...data
					}
				});
			}).catch((e) => {
				dispatch({
					type: REQUEST_ERROR,
					payload: {
						error: e
					}
				});
			});
	}
};

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
		axios.post('./actions-installation.php?action=saveCacheFolderSettings', {
				useCustomCacheFolder: selectors.shouldUseCustomCacheFolder(state),
				customCacheFolder: selectors.getCustomCacheFolder(state)
			})
			.then(() => {
				dispatch(requestReturned());
				onSuccess();
			})
			.catch((e) => {
				dispatch(requestReturned());
				onError(e);
			});
	};
};

export const restartInstallation = (history) => {
	return (dispatch) => {
		dispatch(initActions.clearGlobalError());

		// this shouldn't occur, but...
		console.log(navUtils.getCurrentInstallationPage());
		if (navUtils.getCurrentInstallationPage() !== 1) {
			history.push('/');
		}
	};
};
