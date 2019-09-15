import * as generalUtils from '../generalUtils';


describe('replacePlaceholders', () => {

	test('string without placeholder should be returned as is', () => {
		const str = 'This is a string without placeholders.';
		expect(generalUtils.replacePlaceholders(str, [])).toEqual(str);
	});

	test('string with placeholders, but no actual replacement strings passed', () => {
		const str = 'This is a string with {0} a placeholder.';
		expect(generalUtils.replacePlaceholders(str, [])).toEqual(str);
	});

	test('confirm single placeholder replaced', () => {
		const str = 'This is a string with {0} a placeholder.';
		expect(generalUtils.replacePlaceholders(str, ['REPLACED'])).toEqual('This is a string with REPLACED a placeholder.');
	});

	test('confirm two placeholders replaced', () => {
		const str = 'This is {0} blah {1} a placeholder.';
		expect(generalUtils.replacePlaceholders(str, ['ONE', 'TWO'])).toEqual('This is ONE blah TWO a placeholder.');
	});

	test('check placeholders get switched out regardless of order', () => {
		const str = 'This is {1} blah {0} a placeholder.';
		expect(generalUtils.replacePlaceholders(str, ['ONE', 'TWO'])).toEqual('This is TWO blah ONE a placeholder.');
	});

	test('check every one of the same placeholders gets replaced', () => {
		const str = 'This is {0} blah {0} a {0}';
		expect(generalUtils.replacePlaceholders(str, ['ONE'])).toEqual('This is ONE blah ONE a ONE');
	});
});


describe('evalI18nString', () => {

	it('switches out a single placeholder', () => {
		const string = 'This is a string with a {\$placeholder}.';
		const placeholders = {
			placeholder: 'placeholder'
		};
		expect(generalUtils.evalI18nString(string, placeholders)).toEqual('This is a string with a placeholder.');
	});

	it('switches out multiple placeholders', () => {
		const string = 'This is a string with {\$placeholder1} and {$placeholder2}.';
		const placeholders = {
			placeholder1: 'placeholder1',
			placeholder2: 'placeholder2',
		};
		expect(generalUtils.evalI18nString(string, placeholders)).toEqual('This is a string with placeholder1 and placeholder2.');
	});

	it('switches out the same placeholder multiple times', () => {
		const string = 'This is a string with {\$placeholder1} and {$placeholder1}.';
		const placeholders = {
			placeholder1: 'placeholder1'
		};
		expect(generalUtils.evalI18nString(string, placeholders)).toEqual('This is a string with placeholder1 and placeholder1.');
	});

});
