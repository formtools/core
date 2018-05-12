import React from 'react';
import ReactDOM from 'react-dom';
import 'whatwg-fetch';
import CompatibleComponentsContainer from './containers/CompatibleComponents';
//import store from './store';

// run init.jsx to get the data prereqs for all pages
//store.dispatch(actions.initialize());

// expose whatever we want
window.ReactDOM = ReactDOM;
window.FT = {
	CompatibleComponentsContainer
};
