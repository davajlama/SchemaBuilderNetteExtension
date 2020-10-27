<?php


namespace Davajlama\SchemaBuilderNetteExtension;


use Davajlama\SchemaBuilder\Schema;

interface MigrationInterface
{

    public static function build(Schema $schema);

}