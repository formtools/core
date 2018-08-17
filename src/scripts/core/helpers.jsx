
export const removeFromArray = (array, targetItem) => {
	return array.filter((item) => item !== targetItem);
};


export const decodeEntities = (() => {

    // this prevents any overhead from creating the object each time
    const element = document.createElement('div');

    function decodeHTMLEntities (str) {
        if (str && typeof str === 'string') {
            // strip script/html tags
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