module.exports = function (grunt) {
  'use strict';

  // Project configuration.
  grunt.initConfig({

    // Metadata.
    pkg: grunt.file.readJSON('package.json'),
    
    clean: {
      // site: ['dist', '<%= pkg.name %>.zip', '<%= pkg.main %>.css', 'views/*.handlebars.js']
      site: ['dist', '<%= pkg.name %>.zip', '<%= pkg.main %>.css']
    },
    
    less: {
      site: {
        src: ['less/<%= pkg.main %>.less'],
        dest: '<%= pkg.main %>.css'
      }
    },
    
    handlebars: {
      site: {
        options: {
          namespace: "davemn.templates",
          processName: function(filepath){
            // Example:
            // filepath := "views/table_item.handlebars"
            //   => "table_item"
            
            var filename = filepath.replace(/views\/(.*)\.handlebars/, '$1');
            return filename;
          }
        },
        files: {
          'views/table_item.handlebars.js': 'views/table_item.handlebars'
        }
      }
    },
    
    copy: {
      site: {
        expand: true,
        src: [
          '<%= pkg.main %>.php', '<%= pkg.main %>.css', '.htaccess',
          'bower_components/jquery/dist/**', 
          'bower_components/underscore/*.js',
          'bower_components/handlebars/handlebars.runtime.js', 'bower_components/handlebars/handlebars.runtime.min.js', 
          'bower_components/bootstrap/dist/**',
          'bower_components/backbone/backbone.js', 
          'bower_components/font-awesome/css/**', 'bower_components/font-awesome/fonts/**', 
          'bower_components/html5shiv/dist/**',
          'bower_components/respond/dest/**',
          'bower_components/shared-styles/**',
          'rest/*.php',
          'views/*.handlebars.js'
        ],
        dest: 'dist'
      }
    },
    
    zip: {
      site: {
        cwd: 'dist/',
        src: ['dist/**/*.*', 'dist/**/.*'],
        dest: '<%= pkg.name %>.zip'
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-handlebars');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-zip');

  // Build only.
  grunt.registerTask('build', ['clean', 'less', 'handlebars']);
  
  // Full distribution task.
  grunt.registerTask('dist', ['build', 'copy:site', 'zip']);

  // Default task.
  grunt.registerTask('default', ['dist']);
};
