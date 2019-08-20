import moment from 'moment';



export const removeFromArray = (array, targetItem) => {
	return array.filter((item) => item !== targetItem);
};


export const decodeEntities = (() => {

    // this prevents any overhead from creating the object each time
    const element = document.createElement('div');

    function decodeHTMLEntities (str) {
        if (str && typeof str === 'string') {
            str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
            str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
            element.innerHTML = str;
            str = element.textContent;
            element.textContent = '';
        }
        return str;
    }

    return decodeHTMLEntities;
})();


export const convertHashToArray = (hash) => {
    const arr = [];
    for (let prop in hash) {
        arr.push(hash[prop]);
    }
    return arr;
};


export const formatDatetime = (date, format = "MMM D, YYYY h:mm A") => {
    return moment(date).format(format);
};


/**
 * Useful localization
 * @param str the raw string containing placeholders.
 * @param replacementStrings array of strings. Index 0 will replace {0}, index 1 will replace {1} etc.
 */
export const replacePlaceholders = (str, replacementStrings) => {
	let updatedStr = str;
	replacementStrings.forEach((currStr, index) => {
		const regex = new RegExp('\\{' + index + '\\}', 'g');
		updatedStr = updatedStr.replace(regex, currStr);
	});
	return updatedStr;
};
