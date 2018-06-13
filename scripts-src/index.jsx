import React from 'react';
import ReactDOM from 'react-dom';
import './core/store';
import * as coreActions from './core/actions';
import CompatibleComponentsContainer from './containers/CompatibleComponents/CompatibleComponentsContainer';

// boot 'er up
coreActions.getInitializationData();

// expose whatever we want
window.ReactDOM = ReactDOM;
window.FT = {
	CompatibleComponentsContainer
};
