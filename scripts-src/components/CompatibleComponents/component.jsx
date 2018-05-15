import React, { Component } from 'react';
import PropTypes from 'prop-types';


// used for theme and module rows
const ComponentRow = ({ selected, name, folder, desc, version, toggleRow }) => (
	<tr>
		<td width="30">
			<input type="checkbox" checked={selected} onClick={() => toggleRow(folder)} />
		</td>
		<td>
			{name} <a href=""><b>{version.version}</b></a>
			<p>
				{desc}
			</p>
		</td>
	</tr>
);


/**
 * Used in the installation script to display a full list of all compatible components, grouped by type,
 * with a simple searching.
 */
class CompatibleComponents extends Component {

	render () {
		const { searchFilter, onSearchFilter, api, modules, themes, selected, initialized, dataLoaded, dataLoadError, error,
			onSubmit } = this.props;

//		if (!initialized || !dataLoaded) {
//			return null; // show loading spinner
//		}
		// could probably re-use this EXACT component on the upgrade page

		return (
			<form onSubmit={() => onSubmit()}>
				<div>API</div>

				<div>Themes</div>
				<table>
				{themes.forEach((theme) => <ComponentRow key={theme.folder} {...theme} />)}
				</table>

				<input type="text" placeholder="Filter results" value={searchFilter} onChange={(e) => onSearchFilter(e.target.value)}/>

				<div>Modules</div>
				<table className="list_table">
					<tbody>
					{modules.map((module) => <ComponentRow key={module.folder} {...module} />)}
					</tbody>
				</table>

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
