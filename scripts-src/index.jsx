import React from 'react';
import ReactDOM from 'react-dom';
import CompatibleComponentsContainer from './containers/CompatibleComponents';

// run init.jsx to get the data prereqs for all pages

// expose whatever we want
window.ReactDOM = ReactDOM;
window.FT = {
	CompatibleComponentsContainer
};
