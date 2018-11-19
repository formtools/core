import React from 'react';
import ReactDOM from 'react-dom';
import { actionCreators } from './store/init';
import InstallationComponentsContainer from './containers/InstallationComponents/InstallationComponentsContainer';
import ManageModulesContainer from './containers/ManageModules/ManageModulesContainer';

// boot 'er up
actionCreators.getInitializationData();

window.React = React;
window.ReactDOM = ReactDOM;
window.FT = {
	InstallationComponentsContainer,
	ManageModulesContainer
};
