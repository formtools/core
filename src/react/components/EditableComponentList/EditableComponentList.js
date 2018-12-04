import React, { Component } from 'react';
import styles from './EditableComponentList.scss';
import ComponentList from '../ComponentList/ComponentList';
import { Checkmark } from '../Icons/Icons';


// displays an editable list of components for a single section ('modules', 'themes' etc)
class EditableComponentList extends Component
{
    constructor (props) {
        super(props);
        this.showSection = this.showSection.bind(this);
    }

    showSection (e) {
        const li = e.target.closest('li');
        if (li) {
            this.props.onSelectComponentSection(li.getAttribute('data-section'));
        }
    }

    getAPIIcon () {
        const { isAPISelected, selectedComponentTypeSection } = this.props;
        if (!isAPISelected) {
            return null;
        }

        const color = selectedComponentTypeSection === 'api' ? '#ffffff' : '#999999';
        return (
            <Checkmark size={15} color={color} style={{ marginLeft: 4, verticalAlign: 'sub' }} />
        );
    }

    getSelectAllModulesCheckbox () {
        const { selectedComponentTypeSection, allModulesSelected, toggleAllModulesSelected, i18n } = this.props;

        if (selectedComponentTypeSection !== 'modules') {
            return null;
        }

        return (
            <div className={styles.selectAllRow}>
                <input type="checkbox" id="selectAllModules" checked={allModulesSelected} onChange={toggleAllModulesSelected} />
                <label htmlFor="selectAllModules">{i18n.phrase_select_all}</label>
            </div>
        )
    }

    render () {
        const { selectedComponentTypeSection, modules, themes, api, selectedModuleFolders,
            selectedThemeFolders, toggleComponent, isAPISelected, onShowComponentInfo, i18n } = this.props;

        let components = modules;
        let selectedComponents = selectedModuleFolders;
        if (selectedComponentTypeSection === 'themes') {
            components = themes;
            selectedComponents = selectedThemeFolders;
        } else if (selectedComponentTypeSection === 'api') {
            components = [api];
            selectedComponents = isAPISelected ? ['api'] : [];
        }

        return (
            <div>
                <ul className={styles.pills} onClick={this.showSection}>
                    <li className={(selectedComponentTypeSection === 'modules') ? styles.selected : ''} data-section="modules">
                        {i18n.word_modules}
                        <span>{selectedModuleFolders.length}</span>
                    </li>
                    <li className={(selectedComponentTypeSection === 'themes') ? styles.selected : ''} data-section="themes">
                        {i18n.word_themes}
                        <span>{selectedThemeFolders.length}</span>
                    </li>
                    <li className={(selectedComponentTypeSection === 'api') ? styles.selected : ''} data-section="api">
                        API
                        {this.getAPIIcon()}
                    </li>
                </ul>

                {this.getSelectAllModulesCheckbox()}

                <ComponentList
                    components={components}
                    selectedComponents={selectedComponents}
                    toggleComponent={(folder) => toggleComponent(selectedComponentTypeSection, folder)}
                    isEditing={true}
                    onShowComponentInfo={onShowComponentInfo} />
            </div>
        );
    }
}


export default EditableComponentList;
