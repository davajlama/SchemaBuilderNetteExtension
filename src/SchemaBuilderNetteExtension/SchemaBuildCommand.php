<?php


namespace Davajlama\SchemaBuilderNetteExtension;

use Davajlama\SchemaBuilder\Patch;
use Davajlama\SchemaBuilder\SchemaBuilder;
use Davajlama\SchemaBuilder\SchemaCreator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaBuildCommand extends Command
{
    /** @var MigrationsBuilder */
    private $migrationsBuilder;

    /** @var SchemaBuilder */
    private $schemaBuilder;

    /** @var SchemaCreator */
    private $schemaCreator;

    /**
     * SchemaBuildCommand constructor.
     * @param MigrationsBuilder $migrationsBuilder
     * @param SchemaBuilder $schemaBuilder
     * @param SchemaCreator $schemaCreator
     */
    public function __construct(MigrationsBuilder $migrationsBuilder, SchemaBuilder $schemaBuilder, SchemaCreator $schemaCreator)
    {
        $this->migrationsBuilder = $migrationsBuilder;
        $this->schemaBuilder = $schemaBuilder;
        $this->schemaCreator = $schemaCreator;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('schema:build')
                ->addArgument('hashes', InputArgument::IS_ARRAY, 'List of query hashes')
                ->addOption('dump', 'd', InputOption::VALUE_NONE, 'Dump generated patches')
                ->addOption('safe', 's', InputOption::VALUE_NONE, 'Only non-breakable patches')
                ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply patches to database')
                ->setDescription("Create or update database schema");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hashes         = $input->getArgument('hashes');
        $dump           = $input->getOption('dump');
        $apply          = $input->getOption('apply');
        $safe           = $input->getOption('safe');

        $patches = $this->schemaBuilder->buildSchemaPatches($this->migrationsBuilder->build());

        if($hashes) {
            $patches = $patches->filter(function(Patch $patch) use($hashes){
                return in_array($patch->getHash(), $hashes);
            });
        }

        if($safe) {
            $patches = $patches->filter(function(Patch $patch){
                return $patch->getLevel() === Patch::NON_BREAKABLE;
            });
        }

        foreach ($patches->toArray() as $patch) {
            if($dump) {
                $output->writeln($patch->getQuery());
            } else {
                $color = $patch->getLevel() === Patch::BREAKABLE ? 'fg=red' : 'fg=yellow';
                $output->writeln("<$color>{$patch->getHash()}</> " . $patch->getQuery());
            }
        }

        if($apply) {
            foreach($patches as $patch) {
                try {
                    $output->write("Apply [{$patch->getHash()}]: ");
                    $this->schemaCreator->applyPatch($patch);
                    $output->writeln("<fg=green>DONE</>");
                } catch (\Exception $e) {
                    $output->writeln("<fg=red>" . $e->getMessage() . "</>");
                }
            }
        }
    }
}