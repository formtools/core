import styles from './badge.scss';

const labelMap = {
    module: 'word_module',
    theme: 'word_theme',
    api: 'API'
};

const Badge = ({ type, i18n }) => (
    <span className={`badge ${styles.badge} ${styles[type]}`}>
        {i18n[labelMap[type]]}
    </span>
);

export default Badge;
