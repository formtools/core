const lineByLine = require('n-readlines');
const sass = require('node-sass');
const pkg = require('./package.json');


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

	const findStringsInEnFileMissingFromOtherLangFiles = (stringsByLocale) => {
		const langs = Object.keys(stringsByLocale);

		let count = 0;
		console.log('\nEnglish strings missing from other lang files:\n-------------------------------------------\n');
		Object.keys(stringsByLocale['en_us']).forEach((key) => {
			const missing = [];
			langs.forEach((locale) => {
				if (!stringsByLocale[locale][key]) {
					missing.push(locale);
				}
			});
			if (missing.length > 0) {
				count++;
				console.log(`${key}\n   -missing from: ${missing.join(', ')}\n`);
			}
		});

		if (count > 0) {
			console.log(`-- MISSING ${count}\n`);
		} else {
			console.log('All good!\n');
		}
	};

	const findStringsInOtherFilesNotInEnFile = (stringsByLocale) => {
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
				console.log(`${lang} file contains unused strings: \n-- ${extra.join('\n-- ')}\n`);
			}
		});
	};


	grunt.registerTask('default', ['sync', 'sass', 'concurrent:watchers']);

	grunt.registerTask('parseI18n', () => {
		const stringsByLocale = {};
		pkg.locales.forEach((locale) => {
			stringsByLocale[locale] = getLocaleFileStrings(locale);
		});

		findStringsInEnFileMissingFromOtherLangFiles(stringsByLocale);
		findStringsInOtherFilesNotInEnFile(stringsByLocale);
	});

	// builds everything in the dist folder
	grunt.registerTask('prod', ['sync', 'sass', 'run:webpack_prod']);
};
