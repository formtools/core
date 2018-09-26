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
