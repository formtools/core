import thunk from 'redux-thunk';
import { createStore, combineReducers, applyMiddleware, compose } from 'redux';
import * as compatibleComponents from './components';
import * as constants from './constants';
import * as userInfo from './userInfo';
import * as i18n from './i18n';
import * as init from './init';


function initStore (initialState) {
	let middleware = [thunk];
	let enhancers = [];
	let composeEnhancers = compose;

	if (process.env.NODE_ENV === 'development') {
		const composeWithDevToolsExtension = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__;
		if (typeof composeWithDevToolsExtension === 'function') {
			composeEnhancers = composeWithDevToolsExtension;
		}
	}

	const allReducers = Object.assign({},
		{ components: compatibleComponents.reducer },
		{ constants: constants.reducer },
		{ i18n: i18n.reducer },
		{ userInfo: userInfo.reducer },
		{ init: init.reducer }
	);

	const store = createStore(
		combineReducers(allReducers),
		initialState,
		composeEnhancers(
			applyMiddleware(...middleware),
			...enhancers
		)
	);
	store.asyncReducers = {};

	return store;
}


const store = initStore({});

export default store;
