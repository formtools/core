import axios from 'axios';

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
	return (dispatch) => {
		dispatch(startRequest());
		axios.get('./actions-installation.php?action=saveCacheFolderSettings')
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

