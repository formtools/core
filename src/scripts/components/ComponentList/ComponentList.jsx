import React, { Component } from 'react';
import Badge from '../ComponentTypeBadge/Badge';
import styles from './ComponentList.scss';


class ComponentList extends Component {
    constructor (props) {
        super(props);
        this.viewChangelog = this.viewChangelog.bind(this);
    }

    viewChangelog () {
        const { getChangelog } = this.props;
    }

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
            } else {
                label = 'API';
            }
            return <Badge type={component.type} label={label} />;
        }
    }

    render () {
    	const { components } = this.props;

        return (
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
                            <a href="#" onClick={(e) => { e.preventDefault(); this.viewChangelog(component); }}>About</a>
                        </td>
                    </tr>
                ))}
                </tbody>
            </table>
        );
    }
}

export default ComponentList;
