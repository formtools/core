import React from 'react';
import ReactDOM from 'react-dom';
import { actionCreators } from '../store/init';
import PageContainer from '../containers/Page.container';
import store from '../store';

// boot 'er up. The initialization data is required on every page (i18n, user info, etc)
actionCreators.getInitializationData(store);

const App = () => (
	<PageContainer>
		<div>Content here.</div>
	</PageContainer>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);
