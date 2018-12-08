import React, { Component } from 'react';
import { Pills, Pill } from '../Pills/Pills.component';
import UpgradeComponentList from '../ComponentList/UpgradeComponentList';
import styles from './ManageComponents.scss';


/**
 * Used on the main Modules page within Form Tools to let administrators add, update and remove modules.
 */
class ManageComponents extends Component {
	constructor (props) {
		super(props);
	}

	render () {
		const { i18n, toggleComponentTypeSection, selectedComponentTypeSections, selectedComponents } = this.props;

		return (
			<div className={styles.manageComponents}>
				<div>
					{i18n.word_show}
					<Pills onClick={toggleComponentTypeSection} selected={selectedComponentTypeSections}
					       style={{ display: 'inline-block', margin: '2px 0 12px 10px' }}>
						<Pill id="core">Core</Pill>
						<Pill id="api">API</Pill>
						<Pill id="module">{i18n.word_modules}</Pill>
						<Pill id="theme">{i18n.word_themes}</Pill>
					</Pills>
					<input type="checkbox" id="uninstalled_components" />
						<label htmlFor="uninstalled_components">Show uninstalled components</label>
				</div>

				<UpgradeComponentList components={selectedComponents} i18n={i18n} isEditing={false} showInfoModal={() => {}} />
			</div>
		);
	}
}

export default ManageComponents;
