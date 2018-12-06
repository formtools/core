import React, { Component } from 'react';
import { Pills, Pill } from '../Pills/Pills.component';

/**
 * Used on the main Modules page within Form Tools to let administrators add, update and remove modules.
 */
class ManageComponents extends Component {
	constructor (props) {
		super(props);
		this.toggleSection = this.toggleSection.bind(this);
	}

	toggleSection () {

	}

	render () {
		const { i18n, toggleComponentTypeSection, selectedComponentTypeSections } = this.props;

		console.log('sheesh! ', selectedComponentTypeSections);

		return (
			<div>
				<div>
					{i18n.word_show}

					<Pills onClick={toggleComponentTypeSection} selected={selectedComponentTypeSections}
					       style={{ display: 'inline-block', marginLeft: 10 }}>
						<Pill id="core">
							Core
						</Pill>
						<Pill id="api">
							API
						</Pill>
						<Pill id="modules">
							{i18n.word_modules}
						</Pill>
						<Pill id="themes">
							{i18n.word_themes}
						</Pill>
					</Pills>

					<input type="checkbox" /> Show uninstalled components
				</div>
			</div>
		);
	}
}

export default ManageComponents;
