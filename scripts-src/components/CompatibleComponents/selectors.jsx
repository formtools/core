import { createSelector } from "reselect";

export const searchFilter = (state) => state.compatibleComponents.searchFilter;
export const modules = (state) => state.compatibleComponents.modules;
export const selectedComponents = (state) => state.compatibleComponents.selected;


// expect an array of items with any content, but needs to contain these props: { name: '', desc: '' }
export const getFilteredItems = (searchFilter, list) => {
	const re = new RegExp(searchFilter.toLowerCase());

	return list.filter((item) => {
		return re.test(item.name.toLowerCase()) || re.test(item.desc.toLowerCase());
	}).sort((a) => {
		// return matches on the module name first
		return (re.test(a.name.toLowerCase())) ? -1 : 1;
	});
};


export const addSelectionState = (selectedComponents, list) => {
	return list.map((item) => {
		item.selected = selectedComponents.indexOf(item.folder) !== -1;
		return item;
	});
};

export const modulesWithSelectionState = createSelector(
	selectedComponents,
	modules,
	addSelectionState
);

// returns the list of visible, sorted modules with search filter applied
export const getVisibleModules = createSelector(
	searchFilter,
	modulesWithSelectionState,
	(filter, modules) => getFilteredItems(filter, modules)
);
