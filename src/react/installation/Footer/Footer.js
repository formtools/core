import React from 'react';
import styles from './Footer.scss';

const Footer = ({ i18n }) => (
	<div className={styles.footer}>
		<ul>
			<li><a href="https://formtools.org" target="_blank">formtools.org</a></li>
			<li><a href="https://docs.formtools.org/installation/" target="_blank">{i18n.phrase_installation_help}</a></li>
			<li><a href="https://docs.formtools.org" target="_blank">{i18n.word_documentation}</a></li>
			<li className="colN"><a href="https://github.com/formtools/core/" target="_blank">Github</a></li>
		</ul>
	</div>
);

export default Footer;