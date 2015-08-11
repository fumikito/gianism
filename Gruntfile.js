module.exports = function (grunt) {

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        compass: {

            dist: {
                options: {
                    config: 'assets/compass/config.rb',
                    basePath: 'assets/compass',
                    sourcemap: true
                }
            }

        },

        jshint: {

            options: {
                jshintrc: 'assets/compass/.jshintrc',
                force: true
            },

            files: [
                'assets/compass/js/**/*.js',
                '!assets/compass/js/**/*.min.js'
            ]

        },

        uglify: {
            build: {
                options: {
                    sourceMap: true,
                    mangle: true,
                    compress: true
                },
                files: [{
                    expand: true,
                    cwd: './assets/compass/js/',
                    src: ['**/*.js', '!**/*.min.js'],
                    dest: './assets/compass/js/',
                    ext: '.min.js'
                }]
            }
        },

        watch: {

            js: {
                files: ['assets/compass/js/**/*.js', '!assets/compass/js/**/*.min.js'],
                tasks: ['jshint', 'uglify']
            },

            compass: {
                files: ['assets/compass/sass/*.scss'],
                tasks: ['compass']
            }
        }
    });

    // Load plugins
    grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // Register tasks
    grunt.registerTask('default', ['jshint', 'uglify', 'compass']);

};
