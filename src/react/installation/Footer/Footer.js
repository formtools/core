import React from 'react';
import styles from './Footer.scss';
import { Github } from '../../components/Icons';

const Footer = ({ i18n }) => (
	<div className={styles.footer}>
		<ul>
			<li><a href="https://formtools.org" target="_blank" rel="noopener noreferrer">formtools.org</a></li>
			<li>
				<a href="https://docs.formtools.org/installation/" target="_blank" rel="noopener noreferrer"
					dangerouslySetInnerHTML={{ __html: i18n.phrase_installation_help }} />
			</li>
			<li>
				<a href="https://docs.formtools.org" target="_blank" rel="noopener noreferrer"
					dangerouslySetInnerHTML={{ __html: i18n.word_documentation }} />
			</li>
			<li>
				<a href="https://github.com/formtools/core/" target="_blank" rel="noopener noreferrer">
					<Github size={16} />
					Github
				</a>
			</li>
		</ul>
	</div>
);

export default Footer;