import React, { Component } from 'react';
import styles from './EditableComponentList.scss';
import ComponentList from '../ComponentList/ComponentList';
import { Checkmark } from '../Icons/Icons';
import { Pills, Pill } from '../Pills/Pills.component';


// displays an editable list of components for a single section ('modules', 'themes' etc)
class EditableComponentList extends Component {
    constructor (props) {
        super(props);
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

        if (selectedComponentTypeSection !== 'module') {
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
            selectedThemeFolders, toggleComponent, isAPISelected, showInfoModal, i18n } = this.props;

        let components = modules;
        let selectedComponents = selectedModuleFolders;
        if (selectedComponentTypeSection === 'theme') {
            components = themes;
            selectedComponents = selectedThemeFolders;
        } else if (selectedComponentTypeSection === 'api') {
            components = [api];
            selectedComponents = isAPISelected ? ['api'] : [];
        }

        return (
            <div>
	            <Pills onClick={this.props.onSelectComponentSection} selected={[selectedComponentTypeSection]}
	                style={{ margin: '15px 0 10px' }}>
		            <Pill id="module">
			            {i18n.word_modules}
			            <span>{selectedModuleFolders.length}</span>
		            </Pill>
		            <Pill id="theme">
			            {i18n.word_themes}
			            <span>{selectedThemeFolders.length}</span>
		            </Pill>
		            <Pill id="api">
			            API
			            {this.getAPIIcon()}
		            </Pill>
	            </Pills>

                {this.getSelectAllModulesCheckbox()}

                <ComponentList
                    components={components}
                    selectedComponents={selectedComponents}
                    toggleComponent={(folder) => toggleComponent(selectedComponentTypeSection, folder)}
                    isEditing={true}
                    showInfoModal={showInfoModal} />
            </div>
        );
    }
}


export default EditableComponentList;
