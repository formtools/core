import React from 'react';
import ReactDOM from 'react-dom';
import * as coreActions from './core/actions';
import CompatibleComponentsContainer from './containers/CompatibleComponents/CompatibleComponentsContainer';

// boot 'er up
coreActions.getInitializationData();

window.React = React;
window.ReactDOM = ReactDOM;
window.FT = {
	CompatibleComponentsContainer
};
