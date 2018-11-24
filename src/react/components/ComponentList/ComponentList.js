import React, { Component } from 'react';
import Badge from '../ComponentTypeBadge/Badge';
import styles from './ComponentList.scss';


class ComponentList extends Component {

    getFirstColumn (component) {
        const { isEditing, i18n, selectedComponents, toggleComponent } = this.props;

        if (isEditing) {
            const checked = selectedComponents.indexOf(component.folder) !== -1;
            return <input type="checkbox" checked={checked} onChange={() => toggleComponent(component.folder)} />
        } else {
            let label = '';
            if (component.type === 'module') {
                label = i18n.word_module;
            } else if (component.type === 'theme') {
                label = i18n.word_theme;
            } else if (component.type === 'api') {
                label = 'API';
            } else {
                label = 'Core';
            }
            return <Badge type={component.type} label={label} />;
        }
    }

    render () {
    	const { components, onShowComponentInfo } = this.props;

        return (
        	<div style={{ maxHeight: 350, overflowX: 'scroll' }}>
	            <table className={styles.componentList}>
	                <tbody>
	                {components.map((component, index) => (
	                    <tr className={styles.tr} key={index}>
	                        <td width="55">
	                            {this.getFirstColumn(component)}
	                        </td>
	                        <td width="220">
	                            <h4 className={styles.h4}>{component.name}</h4>
	                        </td>
	                        <td width="200">{component.version}</td>
	                        <td width="200" align="right">
	                            <a href="#" onClick={(e) => { e.preventDefault(); onShowComponentInfo({
	                                componentType: component.type,
	                                folder: component.folder,
	                                version: component.version
	                            }); }}>About</a>
	                        </td>
	                    </tr>
	                ))}
	                </tbody>
	            </table>
	        </div>
        );
    }
}

export default ComponentList;
