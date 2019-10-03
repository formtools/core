import styles from './Badge.scss';

const Badge = ({ type, label }) => (
    <span className={`badge ${styles.badge} ${styles[type]}`}>
        {label}
    </span>
);

export default Badge;
