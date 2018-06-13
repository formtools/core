import React, { Component } from 'react';
//import PropTypes from 'prop-types';


const ComponentRow = ({ selected, name, folder, desc, version, disabled, toggleRow }) => (
	<tr>
		<td width="30" align="center">
			<input type="checkbox" checked={selected} onChange={() => toggleRow(folder)} disabled={disabled} />
		</td>
		<td>
			<b>{name}</b> <a href=""><b>{version}</b></a>
			<div>{desc}</div>
		</td>
	</tr>
);
ComponentRow.defaultProps = {
	disabled: false,
	toggleRow: () => {}
};

/**
 * Used in the installation script to display a full list of all compatible components, grouped by type,
 * with a simple searching.
 */
class CompatibleComponents extends Component {

	render () {
		const { initialized, dataLoaded, dataLoadError, error, searchFilter, onSearchFilter, api, modules, themes,
			onSubmit, toggleAPI, toggleTheme, toggleModule, i18n } = this.props;

		if (!initialized || !dataLoaded) {
			return null;
		} else if (dataLoadError) {
			return <p>Error loading... {error}</p>;
		}

		return (
			<form onSubmit={() => onSubmit()}>
				<br />

				<h3>API</h3>

				<table className="list_table">
					<tbody>
						<tr>
							<td width="30" align="center">
								<input type="checkbox" checked={api.selected} onChange={toggleAPI} />
							</td>
							<td>
								The API (Application Programming Interface) is for developers who wish to submit their
								form data programmatically, or access the database through their own code.
							</td>
						</tr>
					</tbody>
				</table>

				<br />

				<h3>{i18n.word_themes}</h3>

				<table className="list_table">
					<tbody>

					<ComponentRow
						name="Default theme" // localize
						desc="The default theme, bundled with all Form Tools installation. "
						disabled={true} selected={true} />

					{themes.map((theme) => (
						<ComponentRow key={theme.folder}
							name={theme.name}
							folder={theme.folder}
							desc={theme.desc}
							version={theme.version}
							selected={theme.selected}
							toggleRow={toggleTheme}
						/>
					))}
					</tbody>
				</table>

				<br />

				<div style={{ float: 'right', marginTop: 10 }}>
					<input type="text" placeholder="Filter results" value={searchFilter}
						onChange={(e) => onSearchFilter(e.target.value)} />
				</div>


				<h3>Modules</h3>

				<table className="list_table">
					<tbody>
					{modules.map((module) => (
						<ComponentRow key={module.folder}
							  name={module.name}
							  folder={module.folder}
							  desc={module.desc}
							  version={module.version}
							  selected={module.selected}
							  toggleRow={toggleModule}
						/>
					))}
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
//	onSubmit: PropTypes.func.isRequired,
//	initialized: PropTypes.bool,
//	dataLoaded: PropTypes.bool,
//	dataLoadError: PropTypes.bool,
//	error: PropTypes.string,
//	searchFilter: PropTypes.string,
//	i18n: PropTypes.object,
//	constants: PropTypes.object,
//	//api: PropTypes.object,
//	modules: PropTypes.array,
//	themes: PropTypes.array
};

export default CompatibleComponents;
