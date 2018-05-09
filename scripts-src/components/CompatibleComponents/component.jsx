import React, { Component } from 'react';



const ComponentRow = (props) => {
	return (
		<tr>
			<td>
				<input type="checkbox" checked={props.selected} onClick={() => props.toggleRow(props.id)} />
			</td>
			<td>
				{props.name} <b>{props.version}</b>
				<div>
					{props.desc}
				</div>
			</td>
		</tr>
	)
};


/**
 * Used in the installation script to display a full list of all compatible components, grouped by type,
 * with a simple searching.
 */
class CompatibleComponents extends Component {

	render () {
		const { api, modules, themes } = this.props;

		return (
			<div>
				<input type="text" placeholder="Filter results" />

				<div>API</div>
				<table>
					<ComponentRow {...api} />
				</table>

				<div>Modules</div>
				<table>
					{modules.forEach((module) => <ComponentRow {...module} />)}
				</table>

				<div>Themes</div>
				<table>
					{themes.forEach((theme) => <ComponentRow {...theme} />)}
				</table>

				<p>
					<input type="button" value="Download" />
				</p>
			</div>
		);
	}
}
CompatibleComponents.propTypes = {
	i18n: PropTypes.object,
	api: PropTypes.object,
	modules: PropTypes.array,
	themes: PropTypes.array,
	onDownload: PropTypes.func.isRequired
};

export default CompatibleComponents;
