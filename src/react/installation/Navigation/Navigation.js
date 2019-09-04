import React from 'react';
import { withRouter } from 'react-router-dom';
import styles from './navigation.scss';


const getClass = (currentStep, targetStep) => {
	let className;
	if (currentStep === targetStep) {
		className = 'nav_current';
	} else if (currentStep < targetStep) {
		className = 'nav_remaining';
	} else {
		className = 'nav_visited';
	}
	return className;
};

const Navigation = ({ i18n, location, className }) => (
	<div className={`${styles.nav_items} ${className}`}>
		<div className={styles[getClass(location.pathname, '/')]}>
			1 <span className={styles.delim}>-</span> {i18n.word_welcome}
		</div>
		<div className={styles[getClass(location.pathname, '/step2')]}>
			2 <span className={styles.delim}>-</span> {i18n.phrase_system_check}
		</div>
		<div className={styles[getClass(location.pathname, '/step3')]}>
			3 <span className={styles.delim}>-</span> {i18n.phrase_create_database_tables}
		</div>
		<div className={styles[getClass(location.pathname, '/step4')]}>
			4 <span className={styles.delim}>-</span> {i18n.phrase_create_config_file}
		</div>
		<div className={styles[getClass(location.pathname, '/step5')]}>
			5 <span className={styles.delim}>-</span> {i18n.phrase_create_admin_account}
		</div>
		<div className={styles[getClass(location.pathname, '/step6')]}>
			6 <span className={styles.delim}>-</span> {i18n.phrase_clean_up}
		</div>
	</div>
);

export default withRouter(Navigation);