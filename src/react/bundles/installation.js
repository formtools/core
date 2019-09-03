import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { HashRouter as Router, Route } from 'react-router-dom';
import { actions } from '../store/init';
import store from '../store';
import PageLayout from '../installation/Page/Page.container';
import { Step1, Step2, Step3, Step4, Step5, Step6 } from '../installation';

// boot 'er up. The initialization data is required on every page (i18n, user info, etc)
actions.getInstallationInitData(store);

const App = () => (
	<Provider store={store}>
		<Router>
			<PageLayout path="/" component={Step1} />
			<Route path="/step2/" component={Step2} />
			<Route path="/step3/" component={Step3} />
			<Route path="/step4/" component={Step4} />
			<Route path="/step5/" component={Step5} />
			<Route path="/step6/" component={Step6} />
		</Router>
	</Provider>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);