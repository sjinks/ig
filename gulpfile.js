'use strict';

var gulp   = require('gulp');
var del    = require('del');
var exec   = require('gulp-exec');
var gulpif = require('gulp-if');
var gulpLoadPlugins = require('gulp-load-plugins');

const $ = gulpLoadPlugins();

gulp.task('clean', function(done) {
	del(['.tmp', 'public/**', '!public', '!public/uploads']);
	done();
});

gulp.task('clean:images', function(done) {
	del(['public/img']);
	done();
});

gulp.task('clean:styles', function(done) {
	del(['.tmp/styles', 'public/css']);
	done();
});

gulp.task('clean:scripts', function(done) {
	del(['.tmp/scripts', 'public/js']);
	done();
});

gulp.task('clean:files', function(done) {
	del([
		'./public/*',
		'!./public/css',
		'!./public/img',
		'!./public/js',
		'!./public/uploads',
	]);
	done();
});

gulp.task('images', gulp.series('clean:images', function() {
	return gulp.src('public-dev/img/**/*')
		.pipe(
			$.cache(
				$.imagemin({
					progressive: true,
					interlaced: true
				})
			)
		)
		.pipe(gulp.dest('public/img'))
		.pipe($.size({title: 'images'}))
	;
}));

gulp.task('styles', gulp.series('clean:styles', function() {
	const AUTOPREFIXER_BROWSERS = [
		'ie >= 10',
		'ie_mob >= 10',
		'ff >= 30',
		'chrome >= 34',
		'safari >= 7',
		'opera >= 23',
		'ios >= 7',
		'android >= 4.4',
		'bb >= 10'
	];

	return gulp.src(['public-dev/css/**/*.css'])
		.pipe($.newer('.tmp/styles'))
		.pipe($.sourcemaps.init())
		.pipe($.autoprefixer(AUTOPREFIXER_BROWSERS))
		.pipe(gulp.dest('.tmp/styles'))
		.pipe(gulpif('*.css', $.cssnano()))
		.pipe($.size({title: 'styles'}))
		.pipe($.sourcemaps.write('./'))
		.pipe(gulp.dest('public/css'))
		.pipe(gulp.dest('.tmp/styles'))
	;
}));

gulp.task('scripts', gulp.series('clean:scripts', function() {
	return gulp.src(['./public-dev/js/*.js'])
		.pipe($.newer('.tmp/scripts'))
		.pipe($.sourcemaps.init())
		.pipe($.sourcemaps.write())
		.pipe(gulp.dest('.tmp/scripts'))
		.pipe($.uglify({preserveComments: 'some'}))
		.pipe($.size({title: 'scripts'}))
		.pipe($.sourcemaps.write('.'))
		.pipe(gulp.dest('public/js'))
		.pipe(gulp.dest('.tmp/scripts'))
	;
}));

gulp.task('copy', gulp.series('clean:files', function(done) {
	gulp.src([
		'./public-dev/*',
		'!./public-dev/css',
		'!./public-dev/img',
		'!./public-dev/js',
		'!./public-dev/uploads',
	])
		.pipe(exec('mkdir -p public/uploads'))
		.pipe(exec('ln -rfs "<%= file.path %>" ../public/', { cwd: 'public-dev' }))
	;

	done();
}));

gulp.task('default', gulp.parallel('images', 'styles', 'scripts', 'copy'));
