export const getCurrentInstallationPage = () => {
	const navMatch = location.hash.match(/step(\d)/);
	return (navMatch && navMatch.length > 1) ? parseInt(navMatch[1], 10) : 1;
};
