<?php


namespace Davajlama\SchemaBuilderNetteExtension;

use Davajlama\SchemaBuilder\Bridge\DibiAdapter;
use Davajlama\SchemaBuilder\Bridge\NetteDatabaseAdapter;
use Davajlama\SchemaBuilder\Driver\MySqlDriver;
use Davajlama\SchemaBuilder\SchemaBuilder;
use Davajlama\SchemaBuilder\SchemaCreator;
use Nette;
use Nette\DI\CompilerExtension;

class SchemaBuilderNetteExtension extends CompilerExtension
{

    public function beforeCompile()
    {
        $config = $this->getConfig();
        switch($config->adapter) {
            case 'dibi'             : $adapterClass = DibiAdapter::class; break;
            case 'nette-database'   : $adapterClass = NetteDatabaseAdapter::class; break;
            default:
                throw new \Exception("Invalid adapter [$config->adapter]");
        }

        $this->getContainerBuilder()->addDefinition($this->prefix('adapter'))->setType($adapterClass);
        $this->getContainerBuilder()->addDefinition($this->prefix('driver'))->setType(MySqlDriver::class);
        $this->getContainerBuilder()->addDefinition($this->prefix('builder'))->setType(SchemaBuilder::class);
        $this->getContainerBuilder()->addDefinition($this->prefix('creator'))->setType(SchemaCreator::class);
        $this->getContainerBuilder()->addDefinition($this->prefix('command'))
            ->setType(SchemaBuildCommand::class)
            ->addTag('kdyby.console.command');

        $migrationsBuilder = $this->getContainerBuilder()->addDefinition($this->prefix('migrationsBuilder'))
                            ->setType(MigrationsBuilder::class);

        foreach($config->migrations as $migration) {
            $migrationsBuilder->addSetup('addMigration', [$migration]);
        }
    }

    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'adapter'       => Nette\Schema\Expect::anyOf('dibi', 'nette-database')->default('nette-database'),
            'migrations'    => Nette\Schema\Expect::array(),
        ]);
    }

}