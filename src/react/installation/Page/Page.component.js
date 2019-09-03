import React from 'react';
import Header from '../../components/general/Header/Header';
import Footer from '../Footer/Footer';
import Navigation from '../Navigation/Navigation';
import styles from './Page.scss';
import { Route } from "react-router";


const Page = ({ component: Component, ...otherProps }) => {
	const { constants, initialized, i18n } = otherProps;

	if (!initialized) {
		return null;
	}

	return (
		<Route {...otherProps} render={matchProps => (
			<div className={styles.page}>
				<Header
					i18n={i18n}
					constants={constants}
				/>
				<div className={styles.content}>
					<h1>{i18n.word_installation}</h1>

					<Navigation i18n={i18n}/>

					<div className={styles.main}>
						<Component {...matchProps} />
					</div>
				</div>
				<div className={styles.clear}/>
				<Footer i18n={i18n}/>
			</div>
		)} />
	);
};

export default Page;