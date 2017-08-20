var gulp = require('gulp');
var browserSync  = require('browser-sync').create();
var CacheBuster = require('gulp-cachebust');
var cachebust = new CacheBuster();

var $    = require('gulp-load-plugins')();

var sassPaths = [
  'bower_components/foundation-sites/scss',
  'bower_components/motion-ui/src'
];

var scriptPaths = [
    'node_modules/lazysizes/lazysizes.min.js'
];

var ioLazyLoad = [
    'node_modules/intersection-observer/intersection-observer.js',
    'node_modules/iolazyload/dist/js/iolazy.min.js',
    './web/js/lazyload.js'
];


gulp.task('browser-sync', function() {
    browserSync .init({
        proxy: "bolt-extensions.dev/bolt/"
    });
});


// gulp.task('sass', function() {
//   return gulp.src('./web/scss/app1.scss')
//     .pipe($.sass({
//       includePaths: sassPaths,
//       outputStyle: 'compressed' // if css compressed **file size**
//     })
//         .on('error', $.sass.logError))
//       .pipe($.autoprefixer({
//           browsers: ['last 2 versions', 'ie >= 9']
//       }))
//       .pipe(gulp.dest('./web/css'))
//       .pipe(gulp.dest('../../../../public/extensions/vendor/cdowdy/betterthumbs/css'))
//       .pipe(browserSync .stream());
// });

gulp.task( 'docs_css', function () {
    return gulp.src('./web/css/betterthumbs.css')
        // .pipe($.concat('betterthumbs.files.css'))
        // .pipe($.cssnano())
        .pipe(cachebust.resources())
        .pipe($.rename(function (path) {
            path.basename += '.min';
            return path;
        }))
        .pipe(gulp.dest('../../../../public/extensions/vendor/cdowdy/betterthumbs/css'))
        .pipe(gulp.dest('./web/css'))
});

gulp.task('docs_js', function () {
    return gulp.src(['./web/js/betterthumbs.file.delete.js','./web/js/betterthumbs.prime.js' ])
        .pipe($.concat('betterthumbs.js'))
        .pipe($.uglify())
        .pipe(cachebust.resources())
        .pipe($.rename(function (path) {
            path.basename += '.min';
            return path;
        }))
        .pipe(gulp.dest('../../../../public/extensions/vendor/cdowdy/betterthumbs/js'))
        .pipe(gulp.dest('./web/js'))
});

gulp.task('ioLazyLoad', function () {
    return gulp.src(ioLazyLoad)
        .pipe($.concat('lazyLoad.js'))
        .pipe($.uglify())
        .pipe(cachebust.resources())
        .pipe($.rename(function (path) {
            path.basename += '.min';
            return path;
        }))
        .pipe(gulp.dest('../../../../public/extensions/vendor/cdowdy/betterthumbs/js'))
        .pipe(gulp.dest('./web/js'))
});

gulp.task( 'copy_assets', function() {
    return gulp.src( scriptPaths )
        .pipe(cachebust.resources())
        // .pipe($.rename(function (path) {
        //     path.basename += '.min';
        //     return path;
        // }))
        .pipe(gulp.dest('../../../../public/extensions/vendor/cdowdy/betterthumbs/js'))
        .pipe(gulp.dest('./web/js'))
});

gulp.task('default', ['sass'], function() {

  gulp.watch(['./web/scss/**/*.scss'], ['sass']);
  gulp.watch(['../templates/betterthumbs.docs.html.twig']).on('change', browserSync .reload);
});
