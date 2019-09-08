import { arrayUtils } from '../../utils';

export const INIT_DATA_LOADED = 'INIT_DATA_LOADED';
export const INIT_DATA_ERROR_LOADING = 'INIT_DATA_ERROR_LOADING';

// Used during the installation script. It kinda made more sense leaving it here rather than moving the the installation
// actions, since the store needed to listen to those actions anyway & we want to keep the installation code into a
// separate bundle
export const getInstallationData = (store, page) => {
	fetch(`./actions-installation.php?action=init&page=${page}`)
		.then((response) => response.json())
		.then((json) => {
			const { isAuthenticated, availableLanguages, constants, i18n, language } = json;

			// sort by the language string label
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

export const LANGUAGE_UPDATED = 'LANGUAGE_UPDATED';
export const ERROR_UPDATING_LANGUAGE = 'ERROR_UPDATING_LANGUAGE';

export const selectLanguage = (lang) => {
	return (dispatch) => {
		fetch(`./actions-installation.php?action=selectLanguage&lang=${lang}`)
			.then((response) => response.json())
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
