import { createSelector } from 'reselect';

export const isInitialized = (state) => state.init.initialized;
export const getAvailableLanguages = (state) => state.init.availableLanguages;

// returns the list of languages in a format useful for the dropdown list
export const getLanguageList = createSelector(
	getAvailableLanguages,
	(list) => list.map(({ code, lang }) => ({ value: code, label: lang }))
);
