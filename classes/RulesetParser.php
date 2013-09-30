<?php
class RulesetParser
{
    private $pdo;
    private $jsonParser;
    
    public function __construct (PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->jsonParser = new JSONParser();
    }
    
    public function parseRulesetFile ($filePath)
    {
        $object = $this->jsonParser->parseFile($filePath);
        return $this->parseRulesetObject($object);
    }
    
    public function parseRulesetObject ($object)
    {
        $importStatements = $this->parseStatements($object->import);
        $clearStatements  = $this->parseStatements($object->clear);
        
        $variables = isset($object->variables) ? (array) $object->variables : array();
        
        $ruleset = new Ruleset($importStatements, $variables);
        $ruleset->setClearStatements($clearStatements);
        
        return $ruleset;
    }
    
    private function parseStatements ($statementsArray)
    {
        $parsedStatements = array();
        
        foreach ($statementsArray as $unparsedStatement) {
            $parsedStatements[] = $this->parseStatement($unparsedStatement);
        }
        
        return $parsedStatements;
    }
    
    private function parseStatement ($statementObject)
    {
        if (is_string($statementObject)) {
            $stmtString = $statementObject;
            $statementObject = new stdClass;
            $statementObject->statement = $stmtString;
        } else if (!is_object($statementObject)) {
            throw new Exception("Expected statement to be object or string: $statementObject");
        }
        
        return $this->parseStatementObject($statementObject);
    }
        
    private function parseStatementObject ($statementObject)
    {
        $statement = new RuleStatement(
            new TemplatedSQLStatement($this->pdo, $statementObject->statement)
        );
        
        if (isset($statementObject->assign)) {
            $varName = self::scopeVariableName($statementObject->assign);
            $statement->setReturnVariable($varName);
        }
        
        if (isset($statementObject->loop)) {
            $statement->setLoopCommand($statementObject->loop);
        }
        
        return $statement;
    }
    
    private static function scopeVariableName ($varName)
    {
        if ($varName[0] == '$') {
            return substr($varName, 1);
        } else {
            throw new Exception("Expected variable to assign statement result to: $varName");
        }
    }
}
?>
