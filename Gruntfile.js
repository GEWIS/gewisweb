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
        }
    });

    grunt.loadNpmTasks('grunt-contrib-sass');

    grunt.registerTask('default', ['sass:gewis']);
};
