import React from 'react';
import Header from '../components/general/Header/Header';
import Footer from '../components/installation/InstallationFooter/InstallationFooter';
import styles from './Page.scss';


const InstallationPage = ({ children, i18n, constants, rootUrl }) => (
	<div className={styles.page}>
		<Header
			i18n={i18n}
			rootUrl={rootUrl}
			constants={constants}
		/>
		<section>{children}</section>
		<Footer i18n={i18n} />
	</div>
);

export default InstallationPage;