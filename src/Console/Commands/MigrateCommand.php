<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\AppConfig;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleOutputBuilder;
use Tempest\Database\Migrations\MigrationFailed;
use Tempest\Database\Migrations\MigrationManager;
use Tempest\Database\Migrations\MigrationMigrated;
use Tempest\Events\EventHandler;

final class MigrateCommand
{
    private static int $count = 0;

    public function __construct(
        private readonly Console $console,
        private readonly MigrationManager $migrationManager,
        private readonly AppConfig $config,
    ) {
    }

    #[ConsoleCommand(
        name: 'migrate:up',
        description: 'Run all new migrations',
    )]
    public function __invoke(bool $force = false): void
    {
        if (! $force
            && $this->config->environment->isProduction()
            && ! $this->console->confirm("You are running in production. Are you sure you want to continue?")
        ) {
            return;
        }

        $this->migrationManager->up();

        ConsoleOutputBuilder::new()
            ->success("Done")
            ->formatted(sprintf("Migrated %s migrations", self::$count))
            ->blank()
            ->write($this->console);
    }

    #[EventHandler]
    public function onMigrationMigrated(MigrationMigrated $event): void
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
