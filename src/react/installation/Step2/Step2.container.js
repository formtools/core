import React from 'react';
import { connect } from 'react-redux';
import { selectors as i18nSelectors } from '../../store/i18n';
import { actions } from '../store/';
import Step2 from './Step2.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
});

const mapDispatchToProps = (dispatch) => ({
	onSelectLanguage: (lang) => dispatch(actions.selectLanguage(lang))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
