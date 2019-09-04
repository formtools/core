import { arrayUtils } from "../../utils";
import { INIT_DATA_ERROR_LOADING, INIT_DATA_LOADED } from '../../store/init/init.actions';

export const getSystemCheckResults = () => {
	fetch('./actions-installation.php?action=systemCheck')
		.then((response) => response.json())
		.then((json) => {
			const { isAuthenticated, availableLanguages, constants, i18n, userInfo } = json;

			// sort by the language string label
			arrayUtils.sortBy(availableLanguages, 'lang');

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
