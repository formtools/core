import React from 'react';
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

const Navigation = ({ i18n, step = 1 }) => (
	<div className={styles.nav_items}>
		<div className={styles[getClass(step, 1)]}>
			1 <span className={styles.delim}>-</span> {i18n.word_welcome}
		</div>
		<div className={styles[getClass(step, 2)]}>
			2 <span className={styles.delim}>-</span> {i18n.phrase_system_check}
		</div>
		<div className={styles[getClass(step, 3)]}>
			3 <span className={styles.delim}>-</span> {i18n.phrase_create_database_tables}
		</div>
		<div className={styles[getClass(step, 4)]}>
			4 <span className={styles.delim}>-</span> {i18n.phrase_create_config_file}
		</div>
		<div className={styles[getClass(step, 5)]}>
			5 <span className={styles.delim}>-</span> {i18n.phrase_create_admin_account}
		</div>
		<div className={styles[getClass(step, 6)]}>
			6 <span className={styles.delim}>-</span> {i18n.phrase_clean_up}
		</div>
	</div>
);

export default Navigation;