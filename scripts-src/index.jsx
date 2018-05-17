import React from 'react';
import ReactDOM from 'react-dom';
import CompatibleComponentsContainer from './containers/CompatibleComponents';
import './core/init';

// expose whatever we want
window.ReactDOM = ReactDOM;
window.FT = {
	CompatibleComponentsContainer
};
