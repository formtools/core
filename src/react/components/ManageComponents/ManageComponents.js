import React, { Component } from 'react';


/**
 * Used on the main Modules page within Form Tools to let administrators add, update and remove modules.
 */
class ManageComponents extends Component {

	render () {
		return (
			<div>
				<div>
					Show
					<input type="button" value="Modules" />
					<input type="button" value="Themes" />
					<input type="button" value="API" />
					<input type="button" value="Core" />
					|
					<input type="checkbox" /> Show uninstalled components
				</div>
			</div>
		);
	}
}

export default ManageComponents;
