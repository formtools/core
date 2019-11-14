import { connect } from 'react-redux';
import * as componentSelectors from '../../store/components/selectors';
import Step2 from './Step5.component';
import { selectors as i18nSelectors } from '../../store/i18n';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	errorLoading: componentSelectors.isErrorLoading(state)
});

const mapDispatchToProps = (dispatch) => ({});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
