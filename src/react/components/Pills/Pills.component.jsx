import React from 'react';
import PropTypes from 'prop-types';
import styles from './Pills.scss';


export const Pills = () => {
	const childrenWithProps = React.Children.map(children, (child) =>
		React.cloneElement(child, { selected: this.props.selected.indexOf(child.props.id) !== -1 })
	);

	/*
	<ul className={styles.pills} onClick={this.showSection}>
		<li className={(selectedComponentTypeSection === 'modules') ? styles.selected : ''} data-section="modules">
			{i18n.word_modules}
			<span>{selectedModuleFolders.length}</span>
		</li>
		<li className={(selectedComponentTypeSection === 'themes') ? styles.selected : ''} data-section="themes">
			{i18n.word_themes}
			<span>{selectedThemeFolders.length}</span>
		</li>
		<li className={(selectedComponentTypeSection === 'api') ? styles.selected : ''} data-section="api">
			API
			{this.getAPIIcon()}
		</li>
	</ul>
	*/

	return (
		<div className={styles.pills}>{childrenWithProps}</div>
	);
};
Pills.propTypes = {
	children: PropTypes.element
};


export const Pill = ({ id, children }) => (
	<li data-sections={id}>{children}</li>
);
Pill.propTypes = {
	id: PropTypes.string.isRequired
};
