/**
 * Special file that initializes the client side code: it pings the PHP server to get various info about the user's
 * session. Works whether the user is online or not.
 */
import store from './store';
import * as actions from './actions';


export const getInitializationData = () => {
	fetch('../global/react-init.php')
		.then((response) => response.json())
		.then((json) => {
			store.dispatch({
				type: actions.INIT_DATA_LOADED,
				...json
			});
		}).catch((e) => store.dispatch({ // TODO check
			type: actions.INIT_DATA_ERROR_LOADING,
			error: e
		}));
};

getInitializationData();
