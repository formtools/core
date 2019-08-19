import React from 'react';
import Header from '../components/general/Header/Header';
// import Footer from '../components/installation/InstallationFooter/InstallationFooter';
import styles from './Page.scss';


const InstallationPage = ({ children }) => (
	<div className={styles.page}>
		<Header />
		<section>{children}</section>
		...
	</div>
);

export default InstallationPage;