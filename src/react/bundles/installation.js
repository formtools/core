import React from 'react';
import ReactDOM from 'react-dom';
import { actionCreators } from '../store/init';
import InstallationPage from '../layout/InstallationPage';
import store from '../store';

// boot 'er up. The initialization data is required on every page (i18n, user info, etc)
actionCreators.getInitializationData(store);

const App = () => (
	<InstallationPage>
		<div>Content here.</div>
	</InstallationPage>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);
