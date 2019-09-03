import reducerRegistry from '../reducerRegistry';
import { actions } from '../init';

const reducer = (state = {
	rootUrl: null,
	rootDir: null,
	dataSourceUrl: null,
	coreVersion: null
}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		const { rootUrl, rootDir, dataSourceUrl, coreVersion } = action.payload.constants;
		return {
			...state,
			rootUrl,
			rootDir,
			dataSourceUrl,
			coreVersion
		};
	}
	return state;
};

reducerRegistry.register('constants', reducer);
