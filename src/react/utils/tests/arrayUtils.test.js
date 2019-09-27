import * as arrayUtils from '../arrayUtils';

describe('arrayUtils', () => {
	describe('removeFromArray', () => {
		it('removes single item', () => {
			const array1 = [1, 2, 3, 4, 5];
			expect(arrayUtils.removeFromArray(array1, 2)).toEqual([1, 3, 4, 5]);
		});

		it('removes multiple items', () => {
			const array2 = [1, 2, 2, 2, 5];
			expect(arrayUtils.removeFromArray(array2, 2)).toEqual([1, 5]);
		});

		it('uses a strict comparison check', () => {
			const array2 = [1, '2', 2, 2, 5];
			expect(arrayUtils.removeFromArray(array2, 2)).toEqual([1, '2', 5]);
		});

		it('bounds checking', () => {
			const array2 = [];
			expect(arrayUtils.removeFromArray(array2, 2)).toEqual([]);
			expect(arrayUtils.removeFromArray(array2, '')).toEqual([]);
		});
	});

	describe('convertHashToArray', () => {
		it('extracts the property values as expected', () => {
			expect(arrayUtils.convertHashToArray({ one: 1, two: 2, three: 3 })).toEqual([1, 2, 3]);
			expect(arrayUtils.convertHashToArray({ one: null, two: 2, three: 3, five: 'FIVE' })).toEqual([null, 2, 3, 'FIVE']);
		});
	});
});
