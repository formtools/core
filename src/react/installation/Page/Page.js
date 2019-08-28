import React from 'react';
import Header from '../../components/general/Header/Header';
import Footer from '../Footer/Footer';
import Navigation from '../Navigation/Navigation';
import styles from './Page.scss';


const Page = ({ children, i18n, constants, rootUrl }) => (
	<div className={styles.page}>
		<Header
			i18n={i18n}
			rootUrl={rootUrl}
			constants={constants}
		/>
		<div className={styles.content}>
			<h1>{i18n.word_installation}</h1>

			<Navigation i18n={i18n} />
			<div className={styles.main}>
				{children}
			</div>
		</div>
		<div className={styles.clear} />
		<Footer i18n={i18n} />
	</div>
);

export default Page;