const sass = require('node-sass');

module.exports = function (grunt) {

	const config = {
		sass: {
			options: {
				implementation: sass,
				sourceMap: true
			},
			dist: {
				files: {
					'dist/themes/default/dist/css/styles.css': 'src/themes/default/src/sass/index.scss',
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


	grunt.registerTask('default', ['sync', 'concurrent:watchers']);

	// builds everything in the dist folder
	grunt.registerTask('prod', ['sync', 'sass', 'run:webpack_prod']);
};
