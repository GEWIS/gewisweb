module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        sass: {
            bootstrap: {
                files: {
                    'public/css/bootstrap-test.css': 'vendor/twbs/bootstrap-sass/assets/sylesheets/_bootstrap.scss'
                }
            }
        }
    });

    grunt.loadNpmTasks('grub-contrib-sass');

    grunt.registerTask('default', ['sass:bootstrap']);
};
