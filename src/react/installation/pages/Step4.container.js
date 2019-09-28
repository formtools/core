import { connect } from 'react-redux';
import { selectors as i18nSelectors } from '../../store/i18n';
import { actions, selectors } from '../store';
import Step2 from './Step4.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	configFile: selectors.getConfigFileContent(state),
	configFileCreated: selectors.isConfigFileCreated(state)
});

const mapDispatchToProps = (dispatch) => ({
	createConfigFile: (onSuccess, onError) => dispatch(actions.createConfigFile(onSuccess, onError)),
	checkFileExists: (onSuccess, onError) => dispatch(actions.checkConfigFileExists(onSuccess, onError))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
