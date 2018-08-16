import React, { Component } from 'react';
import Badge from '../ComponentTypeBadge/Badge';
import styles from './SelectedComponentList.scss';


class SelectedComponentList extends Component {
    constructor (props) {
        super(props);
        this.viewChangelog = this.viewChangelog.bind(this);
    }

    viewChangelog () {
        const { getChangelog } = this.props;
    }

    render () {
    	const { components, i18n } = this.props;

        return (
            <table className={styles.componentList}>
                {components.map((component, index) => (

                    <tbody className={styles.tbody} key={index}>
                        <tr>
                            <td width="60">
                                <Badge type={component.type} i18n={i18n} />
                            </td>
                            <td width="220">
                                <h4 className={styles.h4}>{component.name}</h4>
                            </td>
                            <td width="200">{component.version}</td>
                            <td width="200" align="right">
                                <a href="#" onClick={(e) => { e.preventDefault(); this.viewChangelog(component); }}>About</a>
                            </td>
                        </tr>
                    </tbody>
                ))}
            </table>
        );
    }
}

export default SelectedComponentList;
