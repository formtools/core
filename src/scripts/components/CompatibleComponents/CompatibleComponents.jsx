import React, { Component } from 'react';
import PropTypes from 'prop-types';
import SelectedComponentList from '../SelectedComponentList/SelectedComponentList';
import EditableComponentList from '../EditableComponentList/EditableComponentList';
import styles from './CompatibleComponents.scss';


class CompatibleComponents extends Component {

	getSelectedComponentList () {
		const { onEditComponentList, selectedComponents, i18n } = this.props;

		return (
			<div>
				<h2>
					Selected Components
				</h2>

				<p>
					We recommend the following components that are useful for the majority of Form Tools installations.
					Click customize to see what other components exist, and tailor your installation to your own
					needs.
				</p>

				<SelectedComponentList components={selectedComponents} i18n={i18n} />

				<p>
					<input type="button" onClick={onEditComponentList} value="Customize" />
                    <span className={styles.delimiter}>|</span>
					<input type="button" value="Continue" />
				</p>
			</div>
		);
	}

	getEditableComponentList () {
        const { onCancelEditComponentList, modules } = this.props;

        return (
            <div>
                <h2>
					Selected Components &raquo; Customize
                </h2>

                <EditableComponentList
                    selected="modules"
                    modules={modules}
                />

                <p>
                    <input type="button" onClick={(e) => { e.preventDefault(); onCancelEditComponentList(); }} value="Cancel" />
                    <span className={styles.delimiter}>|</span>
                    <input type="button" value="Save Changes" />
                </p>

            </div>
        );
	}

	render () {
		const { initialized, dataLoaded, dataLoadError, error, isEditing } = this.props;

		if (!initialized || !dataLoaded) {
			return null;
		} else if (dataLoadError) {
			return <p>Error loading... {error}</p>;
		}

		return (isEditing) ? this.getEditableComponentList() : this.getSelectedComponentList();

		// return (
		// 	<form onSubmit={() => onSubmit()}>
		// 		<br />

		// 		<h3>API</h3>

		// 		<table className="list_table">
		// 			<tbody>
		// 				<tr>
		// 					<td width="30" align="center">
		// 						<input type="checkbox" checked={api.selected} onChange={toggleAPI} />
		// 					</td>
		// 					<td>
		// 						The API (Application Programming Interface) is for developers who wish to submit their
		// 						form data programmatically or access the Form Tools database and methods through their
		// 				own code.
		// 					</td>
		// 				</tr>
		// 			</tbody>
		// 		</table>

		// 		<br />

		// 		<h3>{i18n.word_themes}</h3>

		// 		<table className="list_table">
		// 			<tbody>

		// 			<EditableComponentRow
		// 				name="Default theme" // localize
		// 				desc="The default theme, bundled with all Form Tools installation. "
		// 				disabled={true} selected={true} />

		// 			{themes.map((theme) => (
		// 				<ComponentRow
		// 					key={theme.folder}
		// 					name={theme.name}
		// 					folder={theme.folder}
		// 					desc={theme.desc}
		// 					version={theme.version}
		// 					selected={selectedThemeFolders.includes(theme.folder)}
		// 					toggleRow={toggleTheme}
		// 				/>
		// 			))}
		// 			</tbody>
		// 		</table>

		// 		<br />

		// 		<div style={{ float: 'right', marginTop: 10 }}>
		// 			<input type="text" placeholder="Filter results" value={searchFilter}
		// 				onChange={(e) => onSearchFilter(e.target.value)} />
		// 		</div>


		// 		<h3>Modules</h3>

		// 		<table className="list_table">
		// 			<tbody>
		// 			{modules.map((module) => (
		// 				<EditableComponentRow key={module.folder}
		// 					  name={module.name}
		// 					  folder={module.folder}
		// 					  desc={module.desc}
		// 					  version={module.version}
		// 					  selected={selectedModuleFolders.includes(module.folder)}
		// 					  toggleRow={toggleModule}
		// 				/>
		// 			))}
		// 			</tbody>
		// 		</table>

		// 		<p>
		// 			<input type="submit" value="Continue" />
		// 		</p>
		// 	</form>
		// );
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
//	themes: PropTypes.array,
	selectedModuleFolders: PropTypes.array,
	selectedThemeFolders: PropTypes.array,
};

export default CompatibleComponents;
