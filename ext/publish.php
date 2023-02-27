<?php
/** @var \TypeRocket\Console\Command  $this */

// Copy config files
$file = new TypeRocket\Utility\File(__DIR__ . '/../config');
$file->copyTo(TYPEROCKET_CORE_CONFIG_PATH, false, false, null, 2, false);

// Copy views
$views = new TypeRocket\Utility\File(__DIR__ . '/../views');
$views->copyTo(\TypeRocket\Core\Config::get('paths.views'), false, false, null, 2, false);

// Copy migration files
$migrations_path_pro = __DIR__ . '/../database/migrations';
if(is_dir($migrations_path_pro)) {
    $migrations = new TypeRocket\Utility\File($migrations_path_pro);
    $migrations->copyTo(\TypeRocket\Core\Config::get('paths.migrations'), false, false, null, 2, false);
}

// Add advanced fields
$app_path = \TypeRocket\Core\Config::get('paths.app');
$form_path = $app_path . '/Elements/Form.php';

if(file_exists($form_path)) {
    $form_content = file_get_contents( $form_path );

    if(!\TypeRocket\Utility\Str::contains('AdvancedFields', $form_content)) {
        $eol = PHP_EOL;
        $repl = "\$1 $eol    use \\" . \TypeRocket\Pro\Elements\Traits\AdvancedFields::class . ';';
        $newContent = preg_replace('/(class\s+Form\s+extends\s+BaseForm(\s+)?\{)/m', $repl, $form_content );
        file_put_contents($form_path, $newContent);
    }
}

$this->runCommand('core:update');