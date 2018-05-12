import React, { Component } from 'react';
import PropTypes from 'prop-types';


const ComponentRow = (props) => (
	<tr>
		<td>
			<input type="checkbox" checked={props.selected} onClick={() => props.toggleRow(props.id)} />
		</td>
		<td>
			{props.module_name} <b>{props.version.version}</b>
			<div>
				{props.desc}
			</div>
		</td>
		<td>
			<a href="">Changelog</a>
		</td>
	</tr>
);


/**
 * Used in the installation script to display a full list of all compatible components, grouped by type,
 * with a simple searching.
 */
class CompatibleComponents extends Component {

	render () {
		const { api, modules, themes, initialized, dataLoaded, dataLoadError, error, onSubmit } = this.props;

//		if (!initialized || !dataLoaded) {
//			return null; // show loading spinner
//		}

		// could possibly re-use this EXACT component on the upgrade page........

		return (
			<form onSubmit={() => onSubmit()}>
				<input type="text" placeholder="Filter results" />

				{/*<div>API</div>*/}
				{/*<table>*/}
					{/*<ComponentRow {...api} />*/}
				{/*</table>*/}

				<div>Modules</div>
				<table className="list_table">
					{modules.map((module) => <ComponentRow {...module} />)}
				</table>

				{/*<div>Themes</div>*/}
				{/*<table>*/}
					{/*{themes.forEach((theme) => <ComponentRow {...theme} />)}*/}
				{/*</table>*/}

				<p>
					<input type="submit" value="Continue" />
				</p>
			</form>
		);
	}
}
CompatibleComponents.propTypes = {
	onSubmit: PropTypes.func.isRequired,
	initialized: PropTypes.bool,
	dataLoaded: PropTypes.bool,
	dataLoadError: PropTypes.bool,
	error: PropTypes.string,
	searchFilter: PropTypes.string,
	i18n: PropTypes.object,
	constants: PropTypes.object,
	api: PropTypes.object,
	modules: PropTypes.array,
	themes: PropTypes.array,
};

export default CompatibleComponents;
