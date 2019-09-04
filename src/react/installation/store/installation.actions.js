export const SYSTEM_CHECK_DATA_RETURNED = 'SYSTEM_CHECK_DATA_RETURNED';

export const getSystemCheckResults = () => {
	return (dispatch) => {
		fetch('./actions-installation.php?action=getSystemCheckResults')
			.then((response) => response.json())
			.then((json) => {
				const { results } = json;
				console.log(json);

				dispatch({
					type: SYSTEM_CHECK_DATA_RETURNED,
					payload: {
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

export const START_REQUEST = 'START_REQUEST';
export const REQUEST_ERROR = 'REQUEST_ERROR';