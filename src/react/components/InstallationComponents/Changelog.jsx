import React from 'react';
import PropTypes from 'prop-types';
import styles from './Changelog.scss';
import { formatDatetime } from '../../helpers';
import { Github } from '../Icons/Icons';
import { withStyles } from '@material-ui/core/styles';
import Tooltip from '@material-ui/core/Tooltip';
import IconButton from '@material-ui/core/IconButton';
import VersionBadge from './VersionBadge';

const SmallIconButton = withStyles({
	root: {
		height: 32,
		width: 32,
		padding: 4
	}
})(IconButton);

const Changelog = ({ data, i18n }) => {

    const getGithubIcon = (github_milestone) => {
        if (github_milestone === null) {
            return null;
        }

        return (
            <Tooltip title={i18n.phrase_view_changes_on_github} placement="left">
                <SmallIconButton className={styles.githubIcon} href={data.github_milestone}>
                    <Github color="#999999" />
                </SmallIconButton>
            </Tooltip>
        );
    };

    return (
        <div className={styles.changelog}>
            <div className={`${styles.row} ${styles.rowHeader}`}>
                <div className={styles.colVersion}>{i18n.word_version}</div>
                <div className={styles.colReleaseDate}>{i18n.phrase_release_date}</div>
                <div className={styles.colDesc}>{i18n.phrase_release_notes}</div>
	            <div className={styles.colGithubLink} />
            </div>
            {data.map(({version, release_date, desc, github_milestone}, i) => (
                <div className={styles.row} key={i}>
                    <div className={styles.colVersion}>
                        <VersionBadge label={version}/>
                    </div>
                    <div className={styles.colReleaseDate}>{formatDatetime(release_date, 'MMM D, YYYY')}</div>
                    <div className={styles.colDesc}>{desc}</div>
	                <div className={styles.colGithubLink}>{getGithubIcon(github_milestone)}</div>
                </div>
            ))}
        </div>
    );
};

Changelog.propTypes = {
    data: PropTypes.array
};

export default Changelog;