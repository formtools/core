import React, { Component } from 'react';
import PropTypes from 'prop-types';
import * as helpers from '../../core/helpers';
import ComponentList from '../ComponentList/ComponentList';
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

				<ComponentList components={selectedComponents} i18n={i18n} isEditing={false} />

				<p>
					<input type="button" onClick={onEditComponentList} value="Customize" />
                    <span className={styles.delimiter}>|</span>
					<input type="button" value={helpers.decodeEntities(i18n.word_continue_rightarrow)} />
				</p>
			</div>
		);
	}

	getEditableComponentList () {
        const { onCancelEditComponentList, selectedComponentTypeSection, allModules, allThemes, allModulesSelected,
            onSelectComponentTypeSection, selectedModuleFolders, selectedThemeFolders, toggleComponent,
            toggleAllModulesSelected, api, isAPISelected, saveSelectedComponentList, i18n } = this.props;

        return (
            <div>
                <h2>
					Selected Components &raquo; Customize
                </h2>

                <EditableComponentList
                    selectedComponentTypeSection={selectedComponentTypeSection}
                    onSelectComponentSection={onSelectComponentTypeSection}
                    toggleComponent={toggleComponent}
                    modules={allModules}
                    themes={allThemes}
                    api={api}
                    i18n={i18n}
                    selectedModuleFolders={selectedModuleFolders}
                    selectedThemeFolders={selectedThemeFolders}
                    isAPISelected={isAPISelected}
                    allModulesSelected={allModulesSelected}
                    toggleAllModulesSelected={toggleAllModulesSelected} />

                <p>
                    <input type="button" value={i18n.phrase_save_changes} onClick={saveSelectedComponentList}/>
                    <span className={styles.delimiter}>|</span>
                    <input type="button" onClick={(e) => { e.preventDefault(); onCancelEditComponentList(); }} value={i18n.word_cancel} />
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
	}
}
CompatibleComponents.propTypes = {
	selectedModuleFolders: PropTypes.array,
	selectedThemeFolders: PropTypes.array,
};

export default CompatibleComponents;
