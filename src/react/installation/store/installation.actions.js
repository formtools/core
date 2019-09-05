export const SYSTEM_CHECK_DATA_RETURNED = 'SYSTEM_CHECK_DATA_RETURNED';
export const START_REQUEST = 'START_REQUEST';
export const REQUEST_ERROR = 'REQUEST_ERROR';

export const startRequest = () => ({ type: START_REQUEST });

export const getSystemCheckResults = () => {
	return (dispatch) => {
		dispatch(startRequest());

		fetch('./actions-installation.php?action=getSystemCheckResults')
			.then((response) => response.json())
			.then((json) => {
				dispatch({
					type: SYSTEM_CHECK_DATA_RETURNED,
					payload: {
						...json
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
