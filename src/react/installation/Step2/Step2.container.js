import React from 'react';
import { connect } from 'react-redux';
import { selectors as i18nSelectors } from '../../store/i18n';
import { selectors } from '../store/';
import Step2 from './Step2.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	isLoading: selectors.isLoading(state)
});

const mapDispatchToProps = (dispatch) => ({

});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
