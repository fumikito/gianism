var gulp        = require('gulp'),
    fs          = require('fs'),
    $           = require('gulp-load-plugins')(),
    pngquant    = require('imagemin-pngquant'),
    eventStream = require('event-stream'),
    checktextdomain = require('gulp-checktextdomain');


// Sass tasks
gulp.task('sass', function () {
  return gulp.src(['./src/sass/**/*.scss'])
    .pipe($.plumber({
      errorHandler: $.notify.onError('<%= error.message %>')
    }))
    .pipe($.sourcemaps.init({loadMaps: true}))
    .pipe($.sassBulkImport())
    .pipe($.sass({
      errLogToConsole: true,
      outputStyle    : 'compressed',
      includePaths   : [
        './src/sass'
      ]
    }))
    .pipe($.autoprefixer({browsers: ['last 2 version', '> 5%']}))
    .pipe($.sourcemaps.write('./map'))
    .pipe(gulp.dest('./assets/css'));
});


// Minify All
gulp.task('js', function () {
  return gulp.src(['./src/js/**/*.js'])
    .pipe($.sourcemaps.init({
      loadMaps: true
    }))
    .pipe($.uglify())
    .on('error', $.util.log)
    .pipe($.sourcemaps.write('./map'))
    .pipe(gulp.dest('./assets/js/'));
});


// JS Hint
gulp.task('jshint', function () {
  return gulp.src(['./src/js/**/*.js'])
    .pipe($.jshint('./src/.jshintrc'))
    .pipe($.jshint.reporter('jshint-stylish'));
});

// Build libraries
gulp.task('copylib', function () {
  return eventStream.merge(

    // Copy LigatureSymbols
    gulp.src([
      './src/fonts/**/*'
    ])
      .pipe(gulp.dest('./assets/fonts/')),

    // Copy JS Cookie
    gulp.src([
      './node_modules/js-cookie/src/js.cookie.js'
    ])
      .pipe($.uglify())
      .pipe(gulp.dest('./assets/js/'))

  );
});

// Image min
gulp.task('imagemin', function () {
  return gulp.src('./src/img/**/*')
    .pipe($.imagemin({
      progressive: true,
      svgoPlugins: [{removeViewBox: false}],
      use        : [pngquant()]
    }))
    .pipe(gulp.dest('./assets/img'));
});

gulp.task('checktextdomain', function() {
  return gulp.src('**/*.php')
    .pipe(checktextdomain({
      text_domain   :'gianism',
      keywords      : [
        '__:1,2d',
        '_e:1,2d',
        '_x:1,2c,3d',
        'esc_html__:1,2d',
        'esc_html_e:1,2d',
        'esc_html_x:1,2c,3d',
        'esc_attr__:1,2d',
        'esc_attr_e:1,2d',
        'esc_attr_x:1,2c,3d',
        '_ex:1,2c,3d',
        '_n:1,2,4d',
        '_nx:1,2,4c,5d',
        '_n_noop:1,2,3d',
        '_nx_noop:1,2,3c,4d'
      ],
    }));
});

// watch
gulp.task('watch', function () {
  // Make SASS
  gulp.watch('./src/sass/**/*.scss', ['sass']);
  // JS
  gulp.watch(['./src/js/**/*.js'], ['js', 'jshint']);
  // Minify Image
  gulp.watch('./src/img/**/*', ['imagemin']);
});


// Build
gulp.task('build', ['copylib', 'js', 'sass', 'imagemin']);

// Default Tasks
gulp.task('default', ['watch']);
