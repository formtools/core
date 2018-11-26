import * as helpers from '../helpers';


describe('replacePlaceholders', () => {

	test('string without placeholder should be returned as is', () => {
		const str = 'This is a string without placeholders.';
		expect(helpers.replacePlaceholders(str, [])).toEqual(str);
	});

	test('string with placeholders, but no actual replacement strings passed', () => {
		const str = 'This is a string with {0} a placeholder.';
		expect(helpers.replacePlaceholders(str, [])).toEqual(str);
	});

	test('confirm single placeholder replaced', () => {
		const str = 'This is a string with {0} a placeholder.';
		expect(helpers.replacePlaceholders(str, ['REPLACED'])).toEqual('This is a string with REPLACED a placeholder.');
	});

	test('confirm two placeholders replaced', () => {
		const str = 'This is {0} blah {1} a placeholder.';
		expect(helpers.replacePlaceholders(str, ['ONE', 'TWO'])).toEqual('This is ONE blah TWO a placeholder.');
	});

	test('check placeholders get switched out regardless of order', () => {
		const str = 'This is {1} blah {0} a placeholder.';
		expect(helpers.replacePlaceholders(str, ['ONE', 'TWO'])).toEqual('This is TWO blah ONE a placeholder.');
	});

	test('check every one of the same placeholders gets replaced', () => {
		const str = 'This is {0} blah {0} a {0}';
		expect(helpers.replacePlaceholders(str, ['ONE'])).toEqual('This is ONE blah ONE a ONE');
	});

});
