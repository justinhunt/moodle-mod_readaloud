// Defining requirements
const { src, dest } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
var gulp = require('gulp');
var sourcemaps = require('gulp-sourcemaps');

// Run:
// gulp sass
// Compiles SCSS files in CSS
gulp.task( 'sass', function() {
    return src('./scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass())
    .pipe(sourcemaps.write('.'))
    .pipe(dest('.'));
});


// Run:
// gulp watch
// Starts watcher. Watcher runs gulp sass task on changes
gulp.task( 'watch', function() {
    gulp.watch('./scss/**/*.scss', gulp.series('sass'));
});