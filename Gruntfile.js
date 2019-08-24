module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        sass: {
            options: {
                loadPath: ['vendor/twbs/bootstrap-sass/assets/stylesheets/']
            },
            gewis: {
                files: {
                    'public/css/gewis-theme.css': 'public/scss/main.scss'
                }
            },
            styleguide: {
                files: {
                    'public/styleguide/public/styleguide.css': 'styleguide/styleguide.scss'
                }
            }
        },
        watch: {
            css: {
                files: ['public/scss/**'],
                tasks: ['css']
            },
            styleguide: {
                files: ['public/scss/**', 'styleguide/template/**'],
                tasks: ['styleguide']
            }
        },
        clean: {
            styleguide: ['public/styleguide']
        },
        copy: {
            styleguide: {
                files: [
                    //{ expand: true, cwd: 'public/scss', src: 'styleguide.scss', dest: 'public/styleguide/public', filter: 'isFile' },
                    { expand: true, cwd: 'vendor/twbs/bootstrap-sass/assets/fonts', src: '**', dest: 'public/styleguide/fonts' }
                ]
            }
        },
        shell: {
            kss: {
                command: function () {
                    return 'kss ' + [
                            '--source public/scss',
                            '--destination public/styleguide',
                            '--builder styleguide/custom-builder',
                            '--css public/styleguide.css',
                            '--title "GEWIS Styleguide"'
                        ].join(' ');
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');

    grunt.registerTask('css', ['sass:gewis']);
    grunt.registerTask('styleguide', ['clean:styleguide', 'shell:kss', 'copy:styleguide', 'sass:styleguide']);
    grunt.registerTask('default', ['css']);
};
