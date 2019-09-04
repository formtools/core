
export const sortBy = (arr, prop) => {
	arr.sort((a, b) => {
		if (a[prop] > b[prop]) {
			return 1;
		} else if (a[prop] < b[prop]) {
			return -1;
		}
		return 0;
	});
};
