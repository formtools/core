import { reducers as topLevelReducers } from '../components/Init';
import compatibleComponents from './CompatibleComponents/reducers';

const { init, constants, userInfo, i18n } = topLevelReducers;

export default {
	compatibleComponents,
	init,
	constants,
	userInfo,
	i18n
};
