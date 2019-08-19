import React from 'react';
import ReactDOM from 'react-dom';
import InstallationPage from '../layout/InstallationPage';

const App = () => (
	<InstallationPage>
		<div>Content here.</div>
	</InstallationPage>
);

ReactDOM.render(
	<App />,
	document.getElementById('root')
);
