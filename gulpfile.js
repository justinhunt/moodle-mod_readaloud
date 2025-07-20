// gulpfile.js
// Place this in moodle/mod/readaloud/

const { src, dest, task, watch, series } = require('gulp');
const sass       = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');
const shell      = require('gulp-shell');
const path       = require('path');

// ─────────────────────────────────────────────────────────────────────────────
// 1) Compute your project root (poodll5), three levels up from here:
//    /…/poodll5/moodle/mod/readaloud → /…/poodll5
// ─────────────────────────────────────────────────────────────────────────────
const projectRoot   = path.resolve(__dirname, '../../../');

// ─────────────────────────────────────────────────────────────────────────────
// 2) Locate the Docker-compose wrapper under bin/
// ─────────────────────────────────────────────────────────────────────────────
const composeWrapper = path.join(projectRoot, 'bin', 'moodle-docker-compose');

// ─────────────────────────────────────────────────────────────────────────────
// 3) Compile SCSS → CSS
// ─────────────────────────────────────────────────────────────────────────────
task('sass', () =>
  src('./scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(sourcemaps.write('.'))
    .pipe(dest('.'))
);

// ─────────────────────────────────────────────────────────────────────────────
// 4) Purge caches inside Docker
//
//    We run the wrapper *with* cwd=projectRoot so it finds .env and sets
//    $MOODLE_DOCKER_WWWROOT properly.
// ─────────────────────────────────────────────────────────────────────────────
task('decache', shell.task(
  [
    // full command we want to run:
    // /…/poodll5/bin/moodle-docker-compose exec webserver php admin/cli/purge_caches.php
    `${composeWrapper} exec webserver php admin/cli/purge_caches.php --theme`
  ],
  {
    cwd: projectRoot,
    verbose: true
  }
));

// ─────────────────────────────────────────────────────────────────────────────
// 5) Composite tasks
// ─────────────────────────────────────────────────────────────────────────────
task('compile', series('sass', 'decache'));
task('watch',   ()     => watch('./scss/**/*.scss', series('compile')));
task('default', series('compile', 'watch'));
