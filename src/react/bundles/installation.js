import React from 'react';
import ReactDOM from 'react-dom';
import { actionCreators } from '../store/init';
import Page from '../containers/Page.container';
import store from '../store';

// boot 'er up. The initialization data is required on every page (i18n, user info, etc)
actionCreators.getInstallationInitData(store);

const App = () => (
	<Page>
		<div>Content here...</div>
	</Page>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);