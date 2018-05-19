import React from 'react';
import ReactDOM from 'react-dom';
import './core/store'; // to ensure correct load order
import { actions } from './components/Init';
import CompatibleComponentsContainer from './containers/CompatibleComponents';


// boot 'er up
actions.getInitializationData();

// expose whatever we want
window.ReactDOM = ReactDOM;
window.FT = {
	CompatibleComponentsContainer
};
