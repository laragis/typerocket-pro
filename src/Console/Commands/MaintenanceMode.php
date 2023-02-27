<?php
namespace TypeRocket\Pro\Console\Commands;

use TypeRocket\Console\Command;

class MaintenanceMode extends Command
{

    protected $command = [
        'wp:maintenance',
        'WordPress maintenance mode',
        'This command disable and enabled maintenance mode.'
    ];

    /**
     * Config
     */
    protected function config()
    {
        $this->addArgument('directive', self::OPTIONAL, 'on or off');
    }

    /**
     * Execute Command
     *
     * @return void
     */
    protected function exec()
    {
        $directive = $this->getArgument('directive');
        $file = ABSPATH . '/.maintenance';

        if(!$directive && file_exists($file)) {
            $directive = 'off';
        } elseif(!$directive) {
            $directive = 'on';
        }

        if( $directive == 'off') {
            unlink($file);
            $this->success('Maintenance mode disabled.');
        } else {
            file_put_contents($file, '<?php $upgrading = time(); ?>');
            $this->warning('Maintenance mode enabled.');
        }
    }
}