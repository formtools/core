import React from 'react';
import ReactDOM from 'react-dom';
import { actionCreators } from './store/init';
import InstallationComponentsContainer from './containers/InstallationComponents/InstallationComponentsContainer';
import ManageModulesContainer from './containers/ManageModules/ManageModulesContainer';
import store from './store';

// boot 'er up. The initialization data is required on every page (i18n, user info, etc)
actionCreators.getInitializationData(store);

window.React = React;
window.ReactDOM = ReactDOM;
window.FT = {
	InstallationComponentsContainer,
	ManageModulesContainer
};
