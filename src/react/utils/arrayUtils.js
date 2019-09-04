
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

export const convertHashToArray = (hash) => {
	const arr = [];
	for (let prop in hash) {
		arr.push(hash[prop]);
	}
	return arr;
};

export const removeFromArray = (array, targetItem) => {
	return array.filter((item) => item !== targetItem);
};

