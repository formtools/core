import React from 'react';
import { connect } from 'react-redux';
import { selectors as i18nSelectors } from '../../store/i18n';
import { actions, selectors } from '../store/';
import Step2 from './Step2.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	isLoading: selectors.isLoading(state),
	results: selectors.getSystemCheckResults(state),
	useCustomCacheFolder: selectors.shouldUseCustomCacheFolder(state),
	customCacheFolder: selectors.getCustomCacheFolder(state)
});

const mapDispatchToProps = (dispatch) => ({
	getSystemCheckResults: () => dispatch(actions.getSystemCheckResults()),
	toggleCustomCacheFolder: () => dispatch(actions.toggleCustomCacheFolder()),
	updateCustomCacheFolder: (value) => dispatch(actions.updateCustomCacheFolder(value)),
	saveCacheFolderSetting: (onSuccess, onError) => dispatch(actions.saveCacheFolderSetting(onSuccess, onError))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
