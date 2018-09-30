const sass = require('node-sass');

module.exports = function (grunt) {
	var config = {
		sass: {
			options: {
				implementation: sass,
				sourceMap: true
			},
			dist: {
				files: {
					'themes/default/dist/css/styles.css': 'themes/default/src/sass/index.scss',
				}
			}
		},

		watch: {
			css: {
				files: ['**/*.scss'],
				tasks: ['sass']
			}
		}
	};

	grunt.initConfig(config);

	require('load-grunt-tasks')(grunt);

	grunt.registerTask('default', ['watch']);
};
