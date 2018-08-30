import styles from './VersionBadge.scss';

const VersionBadge = ({ label }) => (
    <span className={`badge ${styles.badge}`}>
        {label}
    </span>
);

export default VersionBadge;
