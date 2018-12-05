import { createSelector } from 'reselect';
import * as componentSelectors from '../../store/components/selectors';


export const isLoaded = createSelector(
	componentSelectors.isCompatibleComponentsDataLoaded,
	componentSelectors.isInstalledComponentsLoaded,
	(compatible, installed) => compatible && installed
);
