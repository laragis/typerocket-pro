<?php
/** @var $this \TypeRocket\Console\Command */
$dest = WP_CONTENT_DIR . '/advanced-cache.php';
$file = __DIR__ . '/../dropins/advanced-cache.php';

if($this->getOption('mode', 'publish') == 'publish') {
    $this->success('Configuring your custom advanced-cache.php');

    // Try to find cache folder context
    $cache_folder = realpath(\TypeRocket\Pro\Extensions\RapidPages::getCacheFolderFullPath());
    $cache_folder = \TypeRocket\Utility\Str::replaceFirst(ABSPATH, '', $cache_folder);

    // Try to find TypeRocket init context
    $tr_path_real = realpath(TYPEROCKET_PATH);
    $tr_path = \TypeRocket\Utility\Str::replaceFirst(ABSPATH, '', $tr_path_real);
    if($tr_path == $tr_path_real) {
        $tr_path = \TypeRocket\Utility\Str::replaceFirst(realpath(ABSPATH . '..'), '..', $tr_path_real);
    }

    // Add advanced cache drop-in
    $eol = PHP_EOL;
    $config = "require ABSPATH . '{$tr_path}/init.php';{$eol}    defined('TYPEROCKET_RAPID_PAGES_FOLDER_PATH') ?: define('TYPEROCKET_RAPID_PAGES_FOLDER_PATH', ABSPATH . '{$cache_folder}');";
    $dest = \TypeRocket\Utility\File::new($dest);
    if($dest->remove()) {
        $this->warning('Removing old ' . $dest);
    }
    $this->success('Adding ' . $dest);
    \TypeRocket\Utility\File::new($file)->copyTemplateFile($dest->file(), ['// {{ config }}'], [$config]);
} else {
    $this->warning('Removing ' . $dest);
    try {
        \TypeRocket\Utility\File::new($dest)->remove();
        \TypeRocket\Pro\Extensions\RapidPages::flush();
    } catch (\Exception $e) {
        $this->error($e->getMessage());
    }
}
