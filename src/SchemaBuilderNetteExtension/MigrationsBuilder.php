<?php


namespace Davajlama\SchemaBuilderNetteExtension;


use Davajlama\SchemaBuilder\Schema;

class MigrationsBuilder
{
    /** @var string[] */
    private $migrations = [];

    /**
     * @param string $class
     * @return $this
     */
    public function addMigration(string $class)
    {
        $this->migrations[] = $class;
        return $this;
    }

    /**
     * @param Schema $schema
     * @return Schema
     * @throws \Exception
     */
    public function build(Schema $schema = null)
    {
        $schema = $schema ?: new Schema();

        foreach($this->migrations as $class) {

            if(!in_array(MigrationInterface::class, class_implements($class))) {
                throw new \Exception("Migration [$class] must be instance of " . MigrationInterface::class);
            }

            $class::build($schema);
        }

        return $schema;
    }
}