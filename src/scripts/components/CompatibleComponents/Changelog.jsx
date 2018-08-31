import React from 'react';
import PropTypes from 'prop-types';
import styles from './Changelog.scss';
import { formatDatetime } from '../../core/helpers';
import { Github } from '../Icons/Icons';
import Tooltip from '@material-ui/core/Tooltip';
import IconButton from '@material-ui/core/IconButton';
import VersionBadge from './VersionBadge';


const Changelog = ({ data, i18n }) => {

    const getGithubIcon = (githubMilestone) => {
        if (!githubMilestone) {
            return null;
        }
        return (
            <Tooltip title="View full changes on github" placement="left">
                <IconButton className={styles.githubIcon} href={githubMilestone}>
                    <Github color="#999999"/>
                </IconButton>
            </Tooltip>
        );
    };

    return (
        <table className={styles.changelog}>
            <tbody>
            <tr>
                <th className={styles.colVersion}>Version</th>
                <th className={styles.colReleaseDate}>Release Date</th>
                <th className={styles.colDesc}>Release Notes</th>
            </tr>
            {data.map(({version, release_date, desc}, i) => (
                <tr key={i}>
                    <td className={styles.colVersion}>
                        <VersionBadge label={version}/>
                    </td>
                    <td className={styles.colReleaseDate}>{formatDatetime(release_date)}</td>
                    <td className={styles.colDesc}>
                        {desc}
                        {getGithubIcon(data.github_milestone)}
                    </td>
                </tr>
            ))}
            </tbody>
        </table>
    );
};

Changelog.propTypes = {
    data: PropTypes.array
};

export default Changelog;