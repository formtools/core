import axios from 'axios';
import { arrayUtils } from '../../utils';

export const INIT_DATA_LOADED = 'INIT_DATA_LOADED';
export const INIT_DATA_ERROR_LOADING = 'INIT_DATA_ERROR_LOADING';

// Used during the installation script. It kinda made more sense leaving it here rather than moving the the installation
// actions, since the store needed to listen to those actions anyway & we want to keep the installation code into a
// separate bundle
export const getInstallationData = (store) => {
	axios.get(`./actions-installation.php?action=init`)
		.then(({ data }) => {
			const { isAuthenticated, availableLanguages, constants, i18n, language } = data;

			// sort by the language name
			arrayUtils.sortBy(availableLanguages, 'lang');

			store.dispatch({
				type: INIT_DATA_LOADED,
				payload: {
					isAuthenticated,
					i18n,
					language,
					availableLanguages,
					constants
				}
			});
		})
		.catch((e) => {
			store.dispatch({
				type: INIT_DATA_ERROR_LOADING,
				payload: {
					error: e
				}
			});
		});
};


export const SET_GLOBAL_ERROR = 'SET_GLOBAL_ERROR';
export const setGlobalError = (error) => ({
	type: SET_GLOBAL_ERROR,
	payload: {
		error
	}
});

export const CLEAR_GLOBAL_ERROR = 'CLEAR_GLOBAL_ERROR';
export const clearGlobalError = () => ({ type: CLEAR_GLOBAL_ERROR });


export const LANGUAGE_UPDATED = 'LANGUAGE_UPDATED';
export const ERROR_UPDATING_LANGUAGE = 'ERROR_UPDATING_LANGUAGE';

export const selectLanguage = (lang) => {
	return (dispatch) => {
		axios.get(`./actions-installation.php?action=selectLanguage&lang=${lang}`)
			.then((json) => {
				const { i18n } = json;
				dispatch({
					type: LANGUAGE_UPDATED,
					payload: {
						i18n
					}
				});
			}).catch((e) => {
			dispatch({
				type: ERROR_UPDATING_LANGUAGE,
				payload: {
					error: e
				}
			});
		});
	}
};
