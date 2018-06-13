import store from './store';

export const INIT_DATA_LOADED = 'INIT_DATA_LOADED';
export const INIT_DATA_ERROR_LOADING = 'INIT_DATA_ERROR_LOADING';

export const getInitializationData = () => {
	fetch('../global/react-init.php')
		.then((response) => response.json())
		.then((json) => {
			store.dispatch({
				type: INIT_DATA_LOADED,
				...json
			});
		}).catch((e) => store.dispatch({ // TODO check
			type: INIT_DATA_ERROR_LOADING,
			error: e
		}));
};
