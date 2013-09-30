<?php
class App
{
    private static $config;
    private static $pdo;
    
    private static $debugMode = false;
    
    private $invocationScript;
    private $jsonParser;
    private $rulesetParser;
    
    
    public static function init ($pathToApp, array $arguments, PDO $pdo)
    {
        self::$pdo = $pdo;
        
        $app = new App($pathToApp);
        $app->run($arguments);
    }
    
    public static function isInDebugMode ()
    {
        return self::$debugMode;
    }
    
    public static function setDebugMode ()
    {
        self::$debugMode = true;
    }
    
    private function __construct ($pathToApp)
    {
        $this->invocationScript = $pathToApp;
        $this->jsonParser = new JSONParser();
        $this->rulesetParser = new RulesetParser(self::$pdo);
    }
    
    private function run (array $parameters)
    {
        $command = array_shift($parameters);
        
        $this->applyParameterOptions($parameters);
        
        switch (strtolower($command)) {
            case 'import':
                $this->assertParameterCount($parameters, 2);
                $this->commandImport($parameters[0], $parameters[1]);
                break;
            case 'clear':
                $this->assertParameterCount($parameters, 1);
                $this->commandClear($parameters[0]);
                break;
            case 'clear+import':
                $this->assertParameterCount($parameters, 2);
                $this->commandClear($parameters[0]);
                $this->commandImport($parameters[0], $parameters[1]);
                break;
            default:
                $this->commandHelp();
                exit(1);
        }
    }
    
    private function applyParameterOptions (array &$parameters)
    {
        $parameters = array_filter($parameters, function($param)
        {
            if (substr($param, 0, 2) == "--") {
                switch (strtolower($param)) {
                    case '--debug':
                        App::setDebugMode();
                        break;
                    default:
                        throw new Exception("Unknown command line option: $param");
                }
                return false;
            } else {
                return true;
            }
        });
    }
    
    private function commandHelp ()
    {
        echo "Usage: {$this->invocationScript} <COMMAND> <COMMAND OPTIONS>\n";
        echo "\n";
        echo "Commands:\n";
        echo "    import <ruleset> <path/to/data.json>        Import the JSON file's data into your SQL database.\n";
        echo "    clear  <ruleset>                            Delete all rows from tables that are used for JSON import.\n";
        echo "    clear+import <ruleset> <path/to/data.json>  Clear existing data, then re-import the JSON data.\n";
        echo "\n";
        echo "    ('ruleset' may be the path to a ruleset file or the name of a directory in the 'rulesets' directory)\n";
        echo "\n";
        echo "Additional options:\n";
        echo "    --debug                                     Print every executed SQL statement.";
    }
    
    private function commandImport ($rulesetPath, $dataFilePath)
    {
        $ruleset = $this->getRulesetOrGroup($rulesetPath);

        $dataObject = $this->jsonParser->parseFile($dataFilePath);
        $dataArray = is_array($dataObject) ? $dataObject : array( $dataObject );

        $insertedRows = 0;
        foreach ($dataArray as $dataObject) {
            $ruleset->import($dataObject, array(), $_insertedRows);
            $insertedRows += $_insertedRows;
        }

        echo "Done. Inserted $insertedRows rows.\n";
    }
    
    private function commandClear ($rulesetPath)
    {
        $ruleset = $this->getRulesetOrGroup($rulesetPath);
        $deletedRows = $ruleset->clear();
        
        echo "Done. Deleted $deletedRows rows.\n";
    }
    
    private function getRulesetOrGroup ($nameOrPath)
    {
        if (RulesetGroup::exists($nameOrPath)) {
            return RulesetGroup::open($nameOrPath, $this->rulesetParser);
        } else if ($this->rulesetFileExists($nameOrPath . ".json")) {
            return $this->loadRulesetFile($nameOrPath . ".json");
        } else {
            return $this->rulesetParser->parseRulesetFile($nameOrPath);
        }
    }
    
    private function rulesetFileExists ($pathInRulesetsDir)
    {
        if (substr_count($pathInRulesetsDir, '/') != 1) {
            return false;
        }
        
        return is_file( dirname(__FILE__) . "/../rulesets/$pathInRulesetsDir" );
    }
    
    private function loadRulesetFile ($pathInRulesetsDir)
    {
        return $this->rulesetParser->parseRulesetFile(
            dirname(__FILE__) . "/../rulesets/$pathInRulesetsDir"
        );
    }
    
    private function assertParameterCount (array $parameters, $count)
    {
        if (count($parameters) != $count) {
            throw new Exception("Expected $count parameters. Got: ".implode(', ', $parameters));
        }
    }
}
?>
