import React from 'react';
import PropTypes from 'prop-types';
import styles from './Changelog.scss';
import { formatDatetime } from '../../core/helpers';
// import IconButton from '@material-ui/core/IconButton';
// import DeleteIcon from '@material-ui/icons/Delete';
import VersionBadge from './VersionBadge';


const Changelog = ({ data }) => (
    <table className={styles.changelog}>
        <tbody>
        <tr>
            <th className={styles.colVersion}>Version</th>
            <th className={styles.colReleaseDate}>Release Date</th>
            <th>Release Notes</th>
            <th className={styles.colGithubMilestone} />
        </tr>
        {data.map(({ version, release_date, desc }, i) => (
            <tr key={i}>
                <td className={styles.colVersion}>
                    <VersionBadge label={version} />
                </td>
                <td className={styles.colReleaseDate}>{formatDatetime(release_date)}</td>
                <td>{desc}</td>
                <td>
                    {/*<IconButton aria-label="Delete">*/}
                        {/*<DeleteIcon />*/}
                    {/*</IconButton>*/}
                </td>
            </tr>
        ))}
        </tbody>
    </table>
);

Changelog.propTypes = {
    data: PropTypes.array
};

export default Changelog;