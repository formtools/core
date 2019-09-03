export const INIT_DATA_LOADED = 'INIT_DATA_LOADED';
export const INIT_DATA_ERROR_LOADING = 'INIT_DATA_ERROR_LOADING';

export const getInstallationInitData = (store) => {
	fetch('./actions-installation.php?action=init')
		.then((response) => response.json())
		.then((json) => {
			const { isAuthenticated, availableLanguages, constants, i18n, userInfo } = json;

			store.dispatch({
				type: INIT_DATA_LOADED,
				payload: {
					isAuthenticated,
					i18n,
					userInfo,
					availableLanguages,
					constants
				}
			});
		}).catch((e) => {
		store.dispatch({
			type: INIT_DATA_ERROR_LOADING,
			payload: {
				error: e
			}
		});
	});
};
