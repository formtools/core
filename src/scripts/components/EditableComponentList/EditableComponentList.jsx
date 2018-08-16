import React, { Component } from 'react';
import styles from './EditableComponentList.scss';
import EditableComponentRow from '../EditableComponentRow/EditableComponentRow';


class EditableComponentList extends Component
{
    render () {
        const { selected, modules } = this.props;

        return (
            <div>
                <ul className={styles.pills}>
                    <li className={selected === 'modules' ? styles.selected : ''}>Modules</li>
                    <li className={selected === 'themes' ? styles.selected : ''}>Themes</li>
                    <li className={selected === 'api' ? styles.selected : ''}>API</li>
                </ul>

                {modules.map(({ selected, name, folder, desc, version, disabled, toggleRow }) => (
                    <EditableComponentRow
                        name={name}
                        folder={folder}
                        desc={desc}
                        version={version}
                        disabled={disabled}
                        toggleRow={toggleRow}
                    />
                ))}
            </div>
        );
    }
}


export default EditableComponentList;
