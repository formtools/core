import React, { Component } from 'react';
import Badge from '../ComponentTypeBadge/Badge';
import styles from './ComponentList.scss';


class UpgradeComponentList extends Component {

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
    	const { components, showInfoModal } = this.props;

        return (
        	<div style={{ maxHeight: 350, overflowX: 'scroll' }}>
	            <table className={styles.componentList}>
		            <thead>
			            <tr className={styles.tr}>
							<th width="25" />
				            <th width="55">Type</th>
				            <th width="220">Component</th>
				            <th width="180">Your Version</th>
				            <th width="180">Newer Version</th>
				            <th width="50" align="right">Info</th>
			            </tr>
		            </thead>
	                <tbody>
	                {components.map((component, index) => (
	                    <tr className={styles.tr} key={index}>
		                    <td width="25">
			                    <input type="checkbox" />
		                    </td>
	                        <td width="55">
	                            {this.getFirstColumn(component)}
	                        </td>
	                        <td width="220">
	                            <h4 className={styles.h4}>{component.name}</h4>
	                        </td>
	                        <td width="180">{component.version}</td>
		                    <td width="180">??</td>
	                        <td width="50" align="right">
	                            <a href="#" onClick={(e) => { e.preventDefault(); showInfoModal({
	                                componentType: component.type,
	                                folder: component.folder,
	                                version: component.version
	                            }); }}>Info</a>
	                        </td>
	                    </tr>
	                ))}
	                </tbody>
	            </table>
	        </div>
        );
    }
}

export default UpgradeComponentList;
