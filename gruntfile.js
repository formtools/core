const fs = require('fs');
const path = require('path');
const lineByLine = require('n-readlines');
const sass = require('node-sass');
const pkg = require('./package.json');
const child_process = require('child_process');

const release_folder = 'formtools_releases';
const formtools_releases_folder = path.resolve(__dirname, '..', release_folder);


// i18n string keys that aren't used in the Core, but useful for multiple other modules, so placed here. These allow
// the `grunt i18n` task to pass
const globalI18nStrings = [
	'word_help',
	'word_wysiwyg',
	'phrase_update_order',
	'phrase_access_type',
	'notify_module_already_installed'
];

let active_modules = [];
let active_themes = [];
let active_api = null;
if (fs.existsSync('./local.dev.js')) {
	const local_dev = require('./local.dev.js');
	active_modules = local_dev.modules;
	active_themes = local_dev.themes;
	active_api = local_dev.hasOwnProperty('api') ? local_dev.api : null;
}


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


// files that get copied over when they change.
const syncSrc = [
	'**',
	'!**/*.scss', // sass files are built separately
	'!react/**',
	'!themes/default/sass/**',
];

// contains the task details for sass for
const sassFiles = [
	{
		src: 'src/themes/default/sass/index.scss',
		dest: 'dist/themes/default/css/styles.css'
	},
	{
		src: ['swatch_*.scss'],
		expand: true,
		cwd: 'src/themes/default/sass/',
		dest: 'dist/themes/default/css/',
		ext: '.css'
	}
];
const sassLint = [
	'src/themes/default/sass/\*.scss'
];

const sassFileWatchers = [
	'**/*.scss'
];

active_themes.forEach((folder) => {
	sassFiles.push({
		src: `../${folder}/sass/index.scss`,
		dest: `../${folder}/css/styles.css`
	});
	sassLint.push(`../${folder}/sass/\*.scss`);
	sassFileWatchers.push(`../${folder}/sass/\*.scss`);
});


const config = {
	sass: {
		options: {
			outputStyle: 'compressed',
			implementation: sass,
			sourceMap: false
		},
		dist: {
			files: sassFiles
		}
	},

	sasslint: {
		target: sassLint
	},

	watch: {
		css: {
			files: sassFileWatchers,
			tasks: ['sass', 'sasslint']
		},
		src: {
			files: ['src/**'],
			tasks: ['sync']
		},
		modules: {
			files: active_modules.map((folder) => path.resolve(__dirname, '..', folder) + '/**'),
			tasks: ['sync']
		}
	},

	sync: {
		main: {
			files: [{
				cwd: 'src',
				src: syncSrc,
				dest: 'dist',
			}],
			verbose: true
		},
		modules: {
			files: active_modules.map((folder) => ({
				cwd: path.resolve(__dirname, '..', folder),
				src: ['**'],
				dest: `dist/modules/${folder.replace(/^module-/, '')}`,
			}))
		},
		themes: {
			files: active_themes.map((folder) => ({
				cwd: path.resolve(__dirname, '..', folder),
				src: ['**'],
				dest: `dist/themes/${folder.replace(/^theme-/, '')}`,
			}))
		},

		api: {
			// there's actually only a single folder, but convenient to return empty array
			files: active_api !== null ? [{
				cwd: path.resolve(__dirname, '..', active_api),
				src: ['**'],
				dest: `dist/global/api/`
			}] : []
		}
	},

	run: {
		webpack_dev: {
			cmd: 'yarn',
			args: [
				'dev'
			]
		},
		webpack_prod: {
			cmd: 'yarn',
			args: [
				'prod'
			]
		}
	},

	concurrent: {
		watchers: {
			options: {
				logConcurrentOutput: true
			},
			tasks: [
				'watch',
				'run:webpack_dev'
			]
		}
	}
};


module.exports = function (grunt) {
	grunt.initConfig(config);

	require('load-grunt-tasks')(grunt);

	const removeKeyFromI18nFiles = (key) => {
		pkg.locales.forEach((locale) => {
			const file = `./src/global/lang/${locale}.php`;
			const lines = new lineByLine(file);

			let line;
			let regex = /^\$LANG\["(.*)"\]\s*=/;
			let updatedFileLines = [];
			while (line = lines.next()) {
				const lineString = line.toString('utf8');
				let match = lineString.match(regex);
				if (match === null || match[1] !== key) {
					updatedFileLines.push(lineString);
				}
			}
			fs.writeFileSync(file, updatedFileLines.join('\n'));
		});
	};


	const addKeyToI18nFiles = (key) => {
		const en = getLocaleFileStrings('en_us');

		if (!en[key]) {
			grunt.fail.fatal('First add the key to the en_us.php file then re-run this command.');
		}

		pkg.locales.filter((locale) => locale !== 'en_us').forEach((locale) => {
			const file = `./src/global/lang/${locale}.php`;
			const lines = new lineByLine(file);

			let line;
			let regex = new RegExp(`^\\$LANG\\["${key}"\\]`);
			let found = false;
			let updatedLines = [];

			while (line = lines.next()) {
				const lineString = line.toString('utf8');
				if (regex.test(lineString)) {
					found = true;
				}
				updatedLines.push(lineString);
			}

			if (!found) {
				updatedLines.push(`$LANG["${key}"] = "${en[key]}";`);
				fs.writeFileSync(file, updatedLines.join('\n'));
				grunt.log.writeln(`${key} added to ${locale} file.`);
			} else {
				grunt.log.writeln(`${key} already found in ${locale} file.`);
			}
		});

	};


	const sortI18nFiles = () => {
		pkg.locales.forEach((locale) => {
			const data = getLocaleFileStrings(locale);
			const file = `./src/global/lang/${locale}.php`;

			const sortedKeys = Object.keys(data).sort();

			let lines = [];
			sortedKeys.forEach((key) => {
				lines.push(`$LANG["${key}"] = "${data[key]}";`);
			});

			fs.writeFileSync(file, '<?php\n\n$LANG = array();\n\n' + lines.join('\n'));
		});
	};


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


	const parseCoreToFindUnusedStrings = (results, en) => {
		let missingKeys = Object.keys(en);

		const ignoreFolders = [
			'src/global/lang/',
			'src/global/vendor/',
			'src/global/codemirror/',
			'src/global/fancybox/',
			'src/global/images/',
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

			if (!(/[php|txt|tpl|html|js]$/.test(file))) {
				return;
			}

			const lines = new lineByLine(file);
			let line;
			while (line = lines.next()) {
				line.toString('ascii');

				// loop through all keys that still haven't been found yet and remove any that are found on the row
				let updatedKeys = [];
				missingKeys.forEach((key) => {
					const regex = new RegExp(key);

					// very kludgy, but the only place Form Tools uses dynamic keys is for dates: ignore all those keys.
					// We also ignore any i18n keys flagged for global use across FT modules
					if (!(/^date_/.test(key)) && !regex.test(line) && globalI18nStrings.indexOf(key) === -1) {
						updatedKeys.push(key);
					}
				});

				missingKeys = updatedKeys;
			}
		});

		if (missingKeys.length > 0) {
			results.error = true;
			results.lines.push(`\nUNUSED KEYS:\n${missingKeys.join('\n  --')}`);
		}
	};

	const publishReleaseBranch = () => {
		const version = grunt.option('release') || null;
		if (!version) {
			console.log('Missing `release` param. Usage: `grunt publish --release=1.2.3`');
			return;
		}

		// git co master
		// git branch -D auto_3.0.14
		// git push origin --delete auto_3.0.14

		child_process.execSync('git checkout releases', { cwd: formtools_releases_folder });
		child_process.execSync('git rm -rf .', { cwd: formtools_releases_folder });
		child_process.execSync('git reset --hard origin/releases', { cwd: formtools_releases_folder });
		child_process.execSync(`cp -R . ../../${release_folder}`, { cwd: path.resolve(__dirname, 'dist') });
		child_process.execSync(`git checkout -b auto_${version}`, { cwd: formtools_releases_folder });
		child_process.execSync(`git add -A`, { cwd: formtools_releases_folder });
		child_process.execSync(`git commit -m "auto publishing ${version}"`, { cwd: formtools_releases_folder });
		child_process.execSync(`git push origin head`, { cwd: formtools_releases_folder });

		console.log(`${version} branch published on github. Now need to manually publish release.`);
	};

	grunt.registerTask('publishReleaseBranch', publishReleaseBranch);

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
		parseCoreToFindUnusedStrings(results, stringsByLocale['en_us']);

		// actually halt the grunt process if there are errors
		const output = results.lines.join('\n');
		if (results.error) {
			grunt.fail.fatal(output);
		} else {
			grunt.log.write(output);
		}
	});

	// helper methods to operate on all lang files at once
	grunt.registerTask('removeI18nKey', () => {
		const key = grunt.option('key') || null;
		if (!key) {
			grunt.fail.fatal("Please enter a key to remove. Format: `grunt removeI18nKey --key=word_goodbye");
		}
		removeKeyFromI18nFiles(grunt.option('key'));
	});

	// assumes the key already exists in en_us.php
	grunt.registerTask('addI18nKey', () => {
		const key = grunt.option('key') || null;
		if (!key) {
			grunt.fail.fatal("Please enter a key to add. Format: `grunt addI18nKey --key=word_hello");
		}
		addKeyToI18nFiles(key);
	});

	grunt.registerTask('sortI18nFiles', sortI18nFiles);

	// grunt publish --release=3.0.17
	grunt.registerTask('publish', [
		//'prod',
		'publishReleaseBranch'
	]);

	// for local dev work. All you need to do is run `grunt`: that creates a dist/ folder containing all the built code,
	// sets up watchers to copy over changed files and generate the CSS from Sass. It also boots up webpack to handle
	// any JS conversion. Be sure to load up the dist/ subfolder in your browser
	grunt.registerTask('default', ['sync', 'sasslint', 'sass', 'concurrent:watchers']);

	// builds everything into the dist folder
	grunt.registerTask('prod', ['i18n', 'sync', 'sass', 'run:webpack_prod']);

};
