<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\AppConfig;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleOutputBuilder;
use Tempest\Database\Migrations\MigrationFailed;
use Tempest\Database\Migrations\MigrationManager;
use Tempest\Database\Migrations\MigrationRolledBack;
use Tempest\Events\EventHandler;

final class MigrateRollbackCommand
{
    private static int $count = 0;

    public function __construct(
        readonly private Console $console,
        readonly private MigrationManager $migrationManager,
        readonly private AppConfig $config,
    ) {
    }

    #[ConsoleCommand(
        name: 'migrate:down',
        description: 'Rollbacks all executed migrations',
    )]
    public function __invoke(bool $force = false): void
    {
        if (! $force
            && $this->config->environment->isProduction()
            && ! $this->console->confirm("You are running in production. Are you sure you want to continue?")
        ) {
            return;
        }

        $this->migrationManager->down();

        ConsoleOutputBuilder::new()
            ->success("Done")
            ->formatted(sprintf("Rolled back %s migrations", self::$count))
            ->blank()
            ->write($this->console);
    }

    #[EventHandler]
    public function onMigrationRolledBack(MigrationRolledBack $event): void
    {
        ConsoleOutputBuilder::new()->add(" - {$event->name}")->write($this->console);
        self::$count += 1;
    }

    #[EventHandler]
    public function onMigrationFailed(MigrationFailed $event): void
    {
        ConsoleOutputBuilder::new()
            ->error(sprintf("Error while executing migration: %s", $event->name ?? 'command'))
            ->error($event->exception->getMessage())
            ->write($this->console);
    }
}
