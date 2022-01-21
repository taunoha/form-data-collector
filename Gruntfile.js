module.exports = function(grunt) {

    'use strict';

    /**
     *
     *        Install dependencies:     npm install
     *
     *             When developing:     grunt dev
     *
     *
     **/
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        project: {
            name: '<%= pkg.name %>',
            version: '<%= pkg.version %>'
        },

        // Min JS
        uglify: {
            options: {
                mangle: true,
                preserveComments: /^!|@preserve|@license|@cc_on/i
            },
            front: {
                src: 'scripts/fdc-front.js',
                dest: 'scripts/fdc-front.min.js'
            },
            admin: {
                src: 'scripts/fdc-admin.js',
                dest: 'scripts/fdc-admin.min.js'
            }
        },

        // SASS
        'dart-sass': {
            options: {
                outputStyle: 'compressed',
                sourceMap: false,
                includePaths: [
                    'node_modules'
                ]
            },
            iframe: {
                files: [{
                    src: 'gfx/fdc-iframe-styles.scss',
                    dest: 'gfx/fdc-iframe-styles.css'
                }]
            },
            admin: {
                files: [{
                    src: 'gfx/fdc-admin-styles.scss',
                    dest: 'gfx/fdc-admin-styles.css'
                }]
            },
        },

        // Watch
        watch: {
            iframe_sass: {
                files: ['gfx/fdc-iframe-styles.scss'],
                tasks: ['dart-sass:iframe']
            },
            admin_sass: {
                files: ['gfx/fdc-admin-styles.scss'],
                tasks: ['dart-sass:admin']
            },
            app: {
                files: ['scripts/*.js', '!scripts/*.min.js'],
                tasks: ['uglify']
            }
        }

    });

    // Load Npm Tasks

    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-dart-sass');

    // Tasks
    grunt.registerTask('dev', ['watch']);
};
