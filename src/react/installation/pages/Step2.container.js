import { connect } from 'react-redux';
import { selectors as i18nSelectors } from '../../store/i18n';
import { actions, selectors } from '../store';
import Step2 from './Step2.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	isLoading: selectors.isLoading(state),
	results: selectors.getSystemInfo(state),
	systemCheckPassed: selectors.isSystemCheckPassed(state),
	uploadFolder: selectors.getUploadFolder(state),
	useCustomCacheFolder: selectors.shouldUseCustomCacheFolder(state),
	defaultCacheFolder: selectors.getDefaultCacheFolder(state),
	customCacheFolder: selectors.getCustomCacheFolder(state)
});

const mapDispatchToProps = (dispatch) => ({
	toggleCustomCacheFolder: () => dispatch(actions.toggleCustomCacheFolder()),
	updateCustomCacheFolder: (value) => dispatch(actions.updateCustomCacheFolder(value)),
	saveCacheFolderSetting: (onSuccess, onError) => dispatch(actions.saveCacheFolderSetting(onSuccess, onError)),
	refreshSystemCheckPage: (onSuccess, onError) => dispatch(actions.refreshSystemCheckPage(onSuccess, onError))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
