import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { HashRouter as Router } from 'react-router-dom';
import axios from 'axios';
import { actions, selectors } from './store/init';
import store from './store';
import InstallationPage from './installation/Page/Page.container';
import { Step1, Step2, Step3, Step4, Step5, Step6 } from './installation';
import { ERRORS } from './constants';
import { navUtils } from './utils';


const initInitializationBundle = () => {

	// append the current page number to all requests
	axios.interceptors.request.use((config) => {
		const page = navUtils.getCurrentInstallationPage();
		if (config.method === 'get') {
			config.url += `&page=${page}`;
		} else {
			config.url += `?page=${page}`;
		}
		return config;
	}, (error) => Promise.reject(error));

	// handle all auth errors for the installation script the same way: boot them back to the homepage and let them
	// know their sessions expired
	axios.interceptors.response.use(
		(response) => response,
		(error) => {
			if (error.request.status === 403) {

				// handle the error as best we can. If the page has already been initialized, show a dialog telling them
				// the bad news that their session has expired. Otherwise just force redirect back to page 1
				const isInitialized = selectors.isInitialized(store.getState());
				if (isInitialized) {
					store.dispatch(actions.setGlobalError(ERRORS.SESSIONS_EXPIRED));
				} else {
					window.location.href = './';
				}
			}

			return Promise.reject(error);
		},
	);

	// boot 'er up. The initialization data is requested on every page (i18n, user info, etc). This request loads as much
	// info as has been inputted so far for the user in their installation process. This lets them refresh the page on any
	// page and not lose anything
	actions.getInstallationData(store, navUtils.getCurrentInstallationPage());
};

initInitializationBundle();


const App = () => (
	<Provider store={store}>
		<Router>
			<InstallationPage exact path="/" component={Step1} />
			<InstallationPage path="/step2/" component={Step2} />
			<InstallationPage path="/step3/" component={Step3} />
			<InstallationPage path="/step4/" component={Step4} />
			<InstallationPage path="/step5/" component={Step5} />
			<InstallationPage path="/step6/" component={Step6} />
		</Router>
	</Provider>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);