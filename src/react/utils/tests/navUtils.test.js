import * as navUtils from '../navUtils';

describe('navUtils', () => {
	describe('getCurrentInstallationPage', () => {
		it('determines the current installation page as expected', () => {
			location.hash = 'step2';
			expect(navUtils.getCurrentInstallationPage()).toEqual(2);

			location.hash = 'step6';
			expect(navUtils.getCurrentInstallationPage()).toEqual(6);
		});

		it('defaults to 1', () => {
			location.hash = '';
			expect(navUtils.getCurrentInstallationPage()).toEqual(1);

			location.hash = 'stepX';
			expect(navUtils.getCurrentInstallationPage()).toEqual(1);
		});
	});
});
