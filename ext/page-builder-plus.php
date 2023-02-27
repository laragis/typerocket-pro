<?php
/** @var \TypeRocket\Console\Command $this */

use TypeRocket\Core\System;
use \TypeRocket\Pro\Extensions\PageBuilderPlus\PageBuilderPlus;

$migrations = __DIR__ . '/../' . PageBuilderPlus::MIGRATIONS_FOLDER;
$migration = \TypeRocket\Database\Migrate::new($migrations, null, PageBuilderPlus::MIGRATIONS_KEY);

if($this->getOption('mode', 'publish') === 'publish') {
    $app_path = \TypeRocket\Core\Config::get('paths.app');
    $page_model_path = $app_path . '/Models/Page.php';

    if(file_exists($page_model_path)) {
        $page_model_content = file_get_contents( $page_model_path );

        if(!\TypeRocket\Utility\Str::contains('BuilderRevisions', $page_model_content)) {
            $eol = PHP_EOL;
            $repl = "\$1 $eol    use \\" . \TypeRocket\Pro\Extensions\PageBuilderPlus\Traits\BuilderRevisions::class . ';' . $eol;
            $newContent = preg_replace('/(class\s+Page\s+extends\s+WPPost(\s+)?\{)/m', $repl, $page_model_content );
            file_put_contents($page_model_path, $newContent);
        }
    }

    $this->success('Running Page Builder Plus Migrations');
    $migration->runMigrationDirectory('up', 999);
} else {
    $this->warning('Rolling back ' . $migrations);
    $migration->runMigrationDirectory('down', 999);
}

System::updateSiteState('flush_rewrite_rules');