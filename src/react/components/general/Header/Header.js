import React from 'react';
import styles from './Header.scss';


const Header = ({ i18n, version }) => (
	<section className={styles.header}>
		<div className={styles.version}>
			<img src="../themes/default/images/account_section_left_green2x.png" border="0" width="8" height="25" />
			<div id="account_section">{version}</div>
			<img src="../themes/default/images/account_section_right_green2x.png" border="0" width="8" height="25" />
		</div>
		<span style={{ float: 'left', paddingTop: 4 }}>
            <a href="http://www.formtools.org" className="no_border">
                <img src="../themes/default/images/logo_green2x.png" border="0" width="220" height="67"/>
            </a>
        </span>
	</section>
);

export default Header;