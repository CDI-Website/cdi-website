var gulp = require('gulp');
var browserSync = require('browser-sync');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');
var sourcemaps = require('gulp-sourcemaps');
var install = require("gulp-install");
var notify = require("gulp-notify");

var settings = {
    domain: 'local.nasa-cdi.com'
};

var paths = {
    css_directory: 'assets/css',
    sass_directory: 'assets/scss',
    js_directory: 'assets/js'
};

paths.scss = [paths.sass_directory + '/**/*.scss'];
paths.css = [paths.css_directory + '/**/*.css'];
paths.scripts = [paths.js_directory + '/src/**/*.js'];

// Run an npm install
gulp.task('install', function () {
    return gulp.src(['./bower.json', './package.json'])
        .pipe(install());
});

// Task to start the browser-sync server and watch for changed files
gulp.task('browser-sync', function () {
    var browserSyncSettings = {
        logSnippet: false,
        open: false,
        ghostMode: false,
        files: paths.css.concat(paths.scripts)
    };

    if (settings.domain.length > 0) {
        browserSyncSettings.proxy = settings.domain;
    } else {
        // Treat like a server
        browserSyncSettings.server = {
            baseDir : '.'
        };

        browserSyncSettings.open = true;
    }

    return browserSync(browserSyncSettings);
});

// Task to compile using compass
gulp.task('sass', function () {
    return gulp.src(paths.scss)
        .pipe(sourcemaps.init())
        .pipe(sass({
            precision: 7
        }))
        .on('error', notify.onError({
            message: function(error) {
                return error.message;
            }
        }))
        .pipe(autoprefixer())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.css_directory));
});

// Watch for files and run the appropriate task
gulp.task('watch', function () {
    gulp.watch(paths.scss, ['sass']);
    gulp.watch(paths.scripts, ['scripts']);
});

// The default task (called when you run `gulp` from cli)
gulp.task('default', ['install', 'sass', 'watch', 'browser-sync']);