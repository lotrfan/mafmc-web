module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
      },
      build: {
        src: 'src/<%= pkg.name %>.js',
        dest: 'build/<%= pkg.name %>.min.js'
      }
    },
      less: {
              development: {
                options: {
                  paths: ["./css"],
                  yuicompress: true
                },
              files: {
                "./css/test-bootstrap.css": "./less/bootstrap.less"
              }
      }
                                  },

  compass: {                  // Task
    dist: {                   // Target
      options: {              // Target options
        sassDir: 'sass',
        cssDir: 'css',
        environment: 'production'
      }
    },
    dev: {                    // Another target
      options: {
        sassDir: 'sass',
        cssDir: 'css'
      }
    }
  },
    watch: {
      css: {
        files: '**/*.scss',
        tasks: ['compass']
      },
      less: {
        files: 'less/*.less',
        tasks: ['less']
      }
    },

    // run with 'grunt copy'
    copy: {
      main: {
        files: [
          // includes files within path
          {expand: true, cwd: 'external/bootstrap-sass-official/assets/fonts/', src: ['**'],  dest: 'assets/fonts' },

          // includes files within path and its sub-directories
          // {expand: true, src: ['path/**'], dest: 'dest/'},

          // makes all src relative to cwd
          // {expand: true, cwd: 'path/', src: ['**'], dest: 'dest/'},

          // flattens results to a single level
          // {expand: true, flatten: true, src: ['path/**'], dest: 'dest/', filter: 'isFile'}
        ]
      }
    }




  });






  grunt.loadNpmTasks('grunt-contrib-watch');

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-less');

  grunt.loadNpmTasks('grunt-contrib-compass');


  // Default task(s).
  grunt.registerTask('default', ['copy', 'uglify', 'sass', 'compass', 'watch']);


};

