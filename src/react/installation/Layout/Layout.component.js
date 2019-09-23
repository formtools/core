import React from 'react';
import Header from '../../components/Header/Header';
import { Route } from 'react-router';
import Footer from '../Footer/Footer';
import Navigation from '../Navigation/Navigation';
import styles from './Layout.scss';
import SessionsExpiredDialog from '../../components/Dialogs/SessionsExpiredDialog';
import LoadingOverlay from '../../components/LoadingOverlay/LoadingOverlay';


const Page = ({ component: Component, ...otherProps }) => {
	const { constants, initialized, loading, i18n, sessionsExpiredError, restartInstallation } = otherProps;

	if (!initialized) {
		return null;
	}

	return (
		<Route {...otherProps} render={(matchProps) => {
			return (
				<div className={styles.page}>
					<Header
						i18n={i18n}
						constants={constants}
					/>
					<div className={styles.content}>
						<h1>{i18n.word_installation}</h1>
						<section className={styles.pageContent}>
							<Navigation i18n={i18n} className={styles.nav} history={matchProps.history}/>

							<div className={styles.body}>
								<Component {...matchProps} />
							</div>
						</section>
					</div>
					<Footer i18n={i18n}/>
					<SessionsExpiredDialog
						open={sessionsExpiredError}
						i18n={i18n}
						onClose={() => restartInstallation(matchProps.history)}
					/>
					<LoadingOverlay visible={loading} />
				</div>
			);
		}} />
	);
};

export default Page;