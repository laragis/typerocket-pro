const mix = require('laravel-mix');

/*
|--------------------------------------------------------------------------
| TypeRocket Professional Assets
|--------------------------------------------------------------------------
|
| When there are updates to the TypeRocket core assets you must also
| compile those assets.
|
| Laravel Mix documentation can be found at https://laravel-mix.com/.
| https://www.npmjs.com/package/webpack-laravel-mix-manifest
|
*/

let pub = 'assets/dist';

global.Mix.manifest.name = 'mix-pro.json';

// Compile
mix.setPublicPath(pub)
    .options({ processCssUrls: false })
    .js('assets/js/pro-core.js', 'js/pro-core.js')
    .js('assets/js/location.field.js', 'js/location.field.js')
    .sass('assets/sass/dev.scss', 'css/dev.css')
    .sass('assets/sass/redactor.scss', 'css/redactor.css');

mix.version();