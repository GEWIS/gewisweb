module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        sass: {
            options: {
                loadPath: ['vendor/twbs/bootstrap-sass/assets/stylesheets/']
            },
            gewis: {
                files: {
                    'public/css/gewis-theme.css': 'public/css/gewis-theme.scss'
                }
            }
        },
        watch: {
            css: {
                files: [ 'public/css/gewis-theme.scss' ],
                tasks: 'css'
            }

        }
    });

    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');

    grunt.registerTask('css', ['sass:gewis']);

    grunt.registerTask('default', ['css']);
};
