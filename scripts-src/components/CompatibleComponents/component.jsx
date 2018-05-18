import React, { Component } from 'react';
import PropTypes from 'prop-types';


// used for theme and module rows
const ComponentRow = ({ selected, name, folder, desc, version, toggleRow }) => (
	<tr>
		<td width="30" align="center">
			<input type="checkbox" checked={selected} onClick={() => toggleRow(folder)} />
		</td>
		<td>
			<b>{name}</b> <a href=""><b>{version.version}</b></a>
			<div>{desc}</div>
		</td>
	</tr>
);

/**
 * Used in the installation script to display a full list of all compatible components, grouped by type,
 * with a simple searching.
 */
class CompatibleComponents extends Component {

	render () {
		const { initialized, dataLoaded, dataLoadError, error, searchFilter, onSearchFilter, api, modules, themes, selected,
			onSubmit } = this.props;

		if (!initialized || !dataLoaded) {
			return null;
		} else if (dataLoadError) {
			return <p>Error loading... {error}</p>;
		}

		// could probably re-use this EXACT component on the upgrade page

		return (
			<form onSubmit={() => onSubmit()}>
				<br />

				<h3>API</h3>

				<div>
					<input type="checkbox" /> The API (Application Programming Interface) is for developers who wish
					to submit their form data or interact with Form Tools programmatically.
				</div>

				<br />

				<h3>Themes</h3>

				<table className="list_table">
					<tbody>
					{themes.map((theme) => <ComponentRow key={theme.folder} {...theme} />)}
					</tbody>
				</table>

				<br />

				<div style={{ float: 'right', marginTop: 10 }}>
					<input type="text" placeholder="Filter results" value={searchFilter} onChange={(e) => onSearchFilter(e.target.value)} />
				</div>

				<h3>Modules</h3>

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
	//api: PropTypes.object,
	modules: PropTypes.array,
	themes: PropTypes.array
};

export default CompatibleComponents;
