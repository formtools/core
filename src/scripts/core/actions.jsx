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
		}).catch((e) => {
			store.dispatch({
				type: INIT_DATA_ERROR_LOADING,
				error: e
			});
		});
};


// pings the server to get the component history for the Core, API, module or theme
export const getComponentInfo = (componentType, version = null) => {
    var url = `../global/code/actions.php?action=get_component_info&type=${componentType}`;
    if (version) {
        url += `&version=${version}`;
    }

    var state = store.getState();
    console.log(state);

    // fetch(url)
    //     .then((response) => response.json())
    //     .then((json) => {
    //         console.log(json);
    //         // store.dispatch({
    //         //     type: INIT_DATA_LOADED,
    //         //     ...json
    //         // });
    //     }).catch((e) => {
    //         store.dispatch({
    //             type: INIT_DATA_ERROR_LOADING,
    //             error: e
    //         });
    //     });
};
