import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { HashRouter as Router, Route } from 'react-router-dom';
import { actions } from '../store/init';
import store from '../store';
import PageLayout from '../installation/Page/Page.container';
import { Step1, Step2, Step3, Step4, Step5, Step6 } from '../installation';

// boot 'er up. The initialization data is required on every page (i18n, user info, etc). This request loads as much
// info as has been inputted so far for the user in their installation process. So they can refresh the page on any
// page and not lose anything
actions.getInstallationData(store);

const App = () => (
	<Provider store={store}>
		<Router>
			<PageLayout exact path="/" component={Step1} />
			<PageLayout path="/step2/" component={Step2} />
			<PageLayout path="/step3/" component={Step3} />
			<PageLayout path="/step4/" component={Step4} />
			<PageLayout path="/step5/" component={Step5} />
			<PageLayout path="/step6/" component={Step6} />
		</Router>
	</Provider>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);