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
                compress: {
                    warnings: false
                },
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
        sass: {
            options: {
                outputStyle: 'compressed',
                sourceComments: false,
                sourceMap: false
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

        // PostCSS
        postcss: {
            options: {
                map: false,
                processors: [
                    require('autoprefixer')({
                        browsers: 'last 2 versions'
                    }),
                    require('postcss-placehold')(),
                    require('postcss-flexbugs-fixes')()
                ]
            },
            iframe: {
                src: 'gfx/fdc-iframe-styles.css'
            },
            admin: {
                src: 'gfx/fdc-admin-styles.css'
            }
        },

        // Watch
        watch: {
            iframe_sass: {
                files: ['gfx/fdc-iframe-styles.scss'],
                tasks: ['sass:iframe', 'postcss:iframe']
            },
            admin_sass: {
                files: ['gfx/fdc-admin-styles.scss'],
                tasks: ['sass:admin', 'postcss:admin']
            },
            app: {
                files: ['scripts/*.js', '!scripts/*.min.js'],
                tasks: ['uglify']
            }
        }

    });

    // Load Npm Tasks

    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-postcss');

    // Tasks
    grunt.registerTask('dev', ['watch']);
};
