<?php
namespace Gemvc\Core;
use Gemvc\Database\Table;
use Gemvc\Database\TableGenerator;

class Runner
{
    /**
     * @var array<mixed> $commands
     */
    private $commands = [];

    public function __construct()
    {
        $this->registerDefaultCommands();
    }

    private function registerDefaultCommands():void
    {
        $this->commands['Migrate'] = [$this, 'handleMigrate'];
    }
    /**
     * Summary of run
     * @param array<string> $argv
     * @return void
     */
    public function run(array $argv):void
    {
        if (count($argv) < 3) {
            echo "Usage: Gemvc <Command> <ClassName>\n";
            exit(1);
        }

        $command = $argv[1];
        $className = $argv[2];

        if (!isset($this->commands[$command])) {
            echo "Invalid command '$command'.\n";
            $this->printAvailableCommands();
            exit(1);
        }
        // @phpstan-ignore-next-line
        call_user_func($this->commands[$command], args: $className);
    }
    /**
     * Summary of handleMigrate
     * @param string $className
     * @return bool
     */
    private function handleMigrate(string $className):bool
    {
        if (!class_exists($className)) {
            echo "Class '$className' not found.\n";
            exit(1);
        }

        // Create instance of the class
        $object = new $className();
        if(!$object instanceof Table)
        {
            echo "The provided object is not a valid table instance.";
            return false;
        }

        // Generate the table
        $tableGenerator = new TableGenerator();
        return $tableGenerator->createTableFromObject($object);
    }

    private function printAvailableCommands():void
    {
        echo "Available commands:\n";
        foreach (array_keys($this->commands) as $command) {
            echo "  - $command\n";
        }
    }
}
