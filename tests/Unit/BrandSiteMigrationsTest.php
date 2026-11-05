<?php

namespace Tests\Unit;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class BrandSiteMigrationsTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_publication_revisions_are_unique_within_each_site(): void
    {
        $database = new Manager;
        $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]);
        $container = $database->getContainer();
        $schema = $database->getConnection()->getSchemaBuilder();
        $container->instance('db.schema', $schema);
        Facade::setFacadeApplication($container);

        $migrations = dirname(__DIR__, 2).'/database/migrations/';
        (require $migrations.'2026_11_04_000000_create_brandsite_publications.php')->up();
        (require $migrations.'2026_11_04_000001_create_brandsite_editor_sessions.php')->up();
        (require $migrations.'2026_11_04_000002_add_brandsite_moderation.php')->up();
        $schema->create('subscription_packages', fn ($table) => $table->id());
        (require $migrations.'2026_11_04_000003_add_brandsite_to_subscription_packages.php')->up();
        (require $migrations.'2026_11_04_000004_create_brandsite_domains.php')->up();
        $this->assertTrue($schema->hasTable('brandsite_domains'));
        $this->assertTrue($schema->hasColumn('subscription_packages', 'brandsite_enabled'));
        $this->assertTrue($schema->hasTable('brandsite_editor_sessions'));
        $this->assertTrue($schema->hasTable('brandsite_events'));
        $this->assertTrue($schema->hasColumn('brandsite_sites', 'suspended_at'));

        $connection = $database->getConnection();
        $first = $connection->table('brandsite_sites')->insertGetId(['store_id' => 21]);
        $second = $connection->table('brandsite_sites')->insertGetId(['store_id' => 22]);
        $publication = ['revision' => 1, 'configuration' => '{}', 'created_at' => '2026-11-04 00:00:00'];
        $connection->table('brandsite_publications')->insert($publication + ['site_id' => $first]);
        $connection->table('brandsite_publications')->insert($publication + ['site_id' => $second]);
        $this->assertSame(2, $connection->table('brandsite_publications')->count());

        $this->expectException(UniqueConstraintViolationException::class);
        $connection->table('brandsite_publications')->insert($publication + ['site_id' => $first]);
    }
}
