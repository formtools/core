const fs = require('fs');
const lineByLine = require('n-readlines');
const sass = require('node-sass');
const pkg = require('./package.json');


const walk = (dir) => {
	let results = [];
	const list = fs.readdirSync(dir);
	list.forEach((file) => {
		file = dir + '/' + file;
		const stat = fs.statSync(file);
		if (stat && stat.isDirectory()) {
			results = results.concat(walk(file));
		} else {
			results.push(file);
		}
	});
	return results;
};


module.exports = function (grunt) {

	const config = {
		sass: {
			options: {
				implementation: sass,
				sourceMap: true
			},
			dist: {
				files: {
					'dist/themes/default/css/styles.css': 'src/themes/default/sass/index.scss',
				}
			}
		},

		watch: {
			css: {
				files: ['**/*.scss'],
				tasks: ['sass']
			},
			src: {
				files: ['src/**'],
				tasks: ['sync']
			}
		},

		sync: {
			main: {
				files: [{
					cwd: 'src',
					src: [
						'**',
						'!**/*.scss', // sass files are built separately
						'!react/**',
						'!themes/default/sass/**'
					],
					dest: 'dist',
				}],
				verbose: true
			}
		},

		uglify: {
			installation_bundles: {
				files: {
					'dist/global/scripts/installation-bundle.js': [
						'src/global/scripts/jquery.js',
						'src/global/scripts/general.js',
						'src/global/scripts/rsv.js',
						'src/global/scripts/jquery-ui-1.8.6.custom.min.js'
					]
				}
			}
		},

		cssmin: {
			target: {
				files: {
					'dist/themes/default/css/installation-bundle.css': [
						'src/themes/default/css/smoothness/jquery-ui-1.8.6.custom.css',
						'dist/themes/default/css/styles.css'
					]
				}
			}
		},

		run: {
			webpack_dev: {
				cmd: 'npm',
				args: [
					'start'
				]
			},
			webpack_prod: {
				cmd: 'npm',
				args: [
					'build'
				]
			}
		},

		concurrent: {
			watchers: {
				options: {
					logConcurrentOutput: true
				},
				tasks: ['watch', 'run:webpack_dev']
			}
		}
	};

	grunt.initConfig(config);

	require('load-grunt-tasks')(grunt);


	const getLocaleFileStrings = (locale) => {
		const en_us_lines = new lineByLine(`./src/global/lang/${locale}.php`);
		let line;
		let regex = /^\$LANG\["(.*)"\]\s*=\s*"(.*)";\s*$/;

		const map = {};
		while (line = en_us_lines.next()) {
			const lineString = line.toString('utf8');
			if (/^\$LANG\[/.test(lineString)) {
				let match = lineString.match(regex);
				if (match !== null) {
					map[match[1]] = match[2];
				}
			}
		}
		return map;
	};

	const findStringsInEnFileMissingFromOtherLangFiles = (results, stringsByLocale) => {
		const langs = Object.keys(stringsByLocale);

		let count = 0;
		results.lines.push('\nEnglish strings missing from other lang files:\n-------------------------------------------');
		Object.keys(stringsByLocale['en_us']).forEach((key) => {
			const missing = [];
			langs.forEach((locale) => {
				if (!stringsByLocale[locale][key]) {
					missing.push(locale);
				}
			});
			if (missing.length > 0) {
				count++;
				results.lines.push(`${key}\n   -missing from: ${missing.join(', ')}`);
			}
		});

		if (count > 0) {
			results.error = true;
			results.lines.push(`-- MISSING ${count}`);
		} else {
			results.lines.push('All good!\n');
		}

		return results;
	};

	const findStringsInOtherFilesNotInEnFile = (results, stringsByLocale) => {
		const langs = Object.keys(stringsByLocale);
		const en = stringsByLocale['en_us'];

		langs.filter((lang) => lang !== 'en_us').forEach((lang) => {
			const extra = [];
			Object.keys(stringsByLocale[lang]).forEach((key) => {
				if (!en[key]) {
					extra.push(key);
				}
			});

			if (extra.length) {
				results.error = true;
				results.lines.push(`${lang} file contains unused strings: \n-- ${extra.join('\n-- ')}`);
			}
		});

		return results;
	};


	const parseCodebaseToFindUnusedStrings = (results, en) => {
		let missingKeys = Object.keys(en);

		const ignoreFolders = [
			'src/global/lang/',
			'src/global/vendor/',
			'dist/',
			'node_modules/',
			'src/modules/'
		];

		const files = walk('./src');
		files.forEach((file) => {

			for (let i=0; i<ignoreFolders.length; i++) {
				const re = new RegExp(ignoreFolders[i]);
				if (re.test(file)) {
					return;
				}
			}

			const lines = new lineByLine(file);
			let line;
			while (line = lines.next()) {
				line.toString('ascii');

				// loop through all keys that still haven't been found yet and remove any that are found on the row
				let updatedKeys = [];
				missingKeys.forEach((key) => {
					const regex = new RegExp(key);

					// very kludgy, but the only place Form Tools uses dynamic keys is for dates: ignore all those keys
					if (!(/^date_/.test(key)) && !regex.test(line)) {
						updatedKeys.push(key);
					}
				});

				missingKeys = updatedKeys;
			}
		});

		if (missingKeys.length > 0) {
			results.error = true;
			results.lines.push(`\nUNUSED KEYS: ${missingKeys.join('\n  --')}`);
		}
	};

	grunt.registerTask('i18n', () => {
		const stringsByLocale = {};
		pkg.locales.forEach((locale) => {
			stringsByLocale[locale] = getLocaleFileStrings(locale);
		});

		let results = {
			error: false,
			lines: []
		};
		findStringsInEnFileMissingFromOtherLangFiles(results, stringsByLocale);
		findStringsInOtherFilesNotInEnFile(results, stringsByLocale);
		parseCodebaseToFindUnusedStrings(results, stringsByLocale['en_us']);

		// actually halt the grunt process if there are errors
		const output = results.lines.join('\n');
		if (results.error) {
			grunt.fail.fatal(output);
		} else {
			grunt.log.write(output);
		}
	});

	// helper methods to operate on all lang files at once
	grunt.registerTask('removeI18nKey', () => {});
	grunt.registerTask('addI18nKey', () => {});
	grunt.registerTask('sortI18nKeys', () => {});


	// for local dev work. All you need to do is run `grunt`: that creates a dist/ folder containing all the built code,
	// plus sets up watchers to copy over changed files, generate sass and the webpack bundles. Be sure to load up the
	// dist folder in your browser
	grunt.registerTask('default', ['sync', 'sass', 'concurrent:watchers']);


	// builds everything into the dist folder
	grunt.registerTask('prod', ['i18n', 'sync', 'sass', 'run:webpack_prod']);

};
