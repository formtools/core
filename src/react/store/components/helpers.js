/**
 * Convenience method to get a unique identifier for a folder-component-type.
 * @param folder
 * @param componentType
 */
export const getComponentIdentifier = (folder, componentType) => {
	let id;
	if (componentType === 'api') {
		id = 'api';
	} else if (componentType === 'module') {
		id = `module_${folder}`;
	} else if (componentType === 'theme') {
		id = `theme_${folder}`;
	}
	return id;
};


/**
 * Converts the identifier back into a human friendly component name.
 */
export const getComponentNameFromIdentifier = (identifier, modules, themes) => {
	let name = '';
	if (identifier === 'api') {
		name = 'API'
	} else if (/^module_/.test(identifier)) {
		const module_folder = identifier.replace(/^module_/, '');
		name = modules[module_folder].name;
	} else if (/^theme_/.test(identifier)) {
		const theme_folder = identifier.replace(/^theme_/, '');
		name = themes[theme_folder].name;
	}
	return name;
};
