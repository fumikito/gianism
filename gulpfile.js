const gulp        = require('gulp');
const fs          = require('fs');
const $           = require('gulp-load-plugins')();
const pngquant    = require('imagemin-pngquant');
const mergeStream = require('merge-stream');


// Sass tasks
gulp.task('sass', function () {
  return gulp.src(['./src/sass/**/*.scss'])
    .pipe($.plumber({
      errorHandler: $.notify.onError('<%= error.message %>')
    }))
    .pipe($.sourcemaps.init({loadMaps: true}))
    .pipe($.sassGlob())
    .pipe($.sass({
      errLogToConsole: true,
      outputStyle    : 'compressed',
      includePaths   : [
        './src/sass'
      ]
    }))
    .pipe($.autoprefixer())
    .pipe($.sourcemaps.write('./map'))
    .pipe(gulp.dest('./assets/css'));
});


// Minify All
gulp.task('js', function () {
  return gulp.src(['./src/js/**/*.js'])
      .pipe($.plumber({
          errorHandler: $.notify.onError('<%= error.message %>')
      }))
    .pipe($.sourcemaps.init({
      loadMaps: true
    }))
    .pipe($.uglify())
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
  return mergeStream(
    // Copy LigatureSymbols
    gulp.src([
      './src/fonts/**/*'
    ])
      .pipe(gulp.dest('./assets/fonts/')),
    // Copy JS Cookie
    gulp.src([
      './node_modules/js-cookie/dist/js.cookie.min.js',
        './node_modules/js-cookie/dist/js.cookie.js'
    ])
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


// watch
gulp.task('watch', function ( done ) {
  // Make SASS
  gulp.watch('./src/sass/**/*.scss', gulp.task( 'sass' ) );
  // JS
  gulp.watch(['./src/js/**/*.js'], gulp.parallel( 'js', 'jshint' ) );
  // Minify Image
  gulp.watch('./src/img/**/*', gulp.task( 'imagemin' ) );
  // Done.
  done();
});


// Build
gulp.task('build', gulp.parallel( 'copylib', 'js', 'sass', 'imagemin' ) );

// Default Tasks
gulp.task('default', gulp.parallel( 'watch' ) );
