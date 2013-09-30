<?php
class RuleStatement
{
    private $sqlStatement;
    private $loopCommand;
    private $returnVar;
    private $evaluator;
    
    public function __construct (TemplatedSQLStatement $statement)
    {
        $this->sqlStatement = $statement;
        $this->evaluator = new ExpressionEvaluator();
    }
    
    public function setLoopCommand ($loopCommand)
    {
        $this->loopCommand = $loopCommand;
    }
    
    public function setReturnVariable ($scopeVariableName)
    {
        $this->returnVar = $scopeVariableName;
    }
    
    public function execute (array &$scope, &$insertedRows=0)
    {
        if ($this->loopCommand) {
            $insertedRows = $this->executeInLoop($this->loopCommand, $scope, function($scope) {
                return $this->sqlStatement->execute($scope);
            });
        } else {
            $statementString = $this->sqlStatement->getStatementString();
            $insertedRows = 0;
            $result = $this->executeSQLStatement($scope, $insertedRows);
            
            if ($this->returnVar) {
                $scope[$this->returnVar] = $result;
            }
            
            return $result;
        }
    }
    
    private function executeInLoop ($loopCommand, array $scope, $callback)
    {
        $this->evaluator->setScope($scope);
        $originalProperties = $scope['_properties'];
        
        if (preg_match('/^FOREACH \s* \( ([\$@][\w\._]+) \s+ AS \s+ \$([\w\._]+) \)$/ix', $loopCommand, $matches)) {
            $arrayToIterate = $this->evaluator->evaluate($matches[1]);
            $iterationVar   = $matches[2];
            $insertedRows   = 0;
            
            foreach ($arrayToIterate as $item) {
                $scope[$iterationVar] = $item;
                
                if (is_object($item)) {
                    self::scopeSetObjectProperties($scope, $item);
                }
                
                $this->executeSQLStatement($scope, $_insertedRows);
                $insertedRows += $_insertedRows;
            }
            
            $scope['_properties'] = $originalProperties;
            return $insertedRows;
        } else {
            throw new Exception("Invalid loop command: $loopCommand");
        }
    }
    
    private function executeSQLStatement ($scope, &$insertedRows=0)
    {
        $insertedRows = 0;
        $statementString = $this->sqlStatement->getStatementString();
        $result = $this->sqlStatement->execute($scope);
        
        if (preg_match('/^INSERT INTO/i', $statementString)) {
            $result = $this->sqlStatement->getPDO()->lastInsertId();
            $insertedRows = 1;
        } else if (preg_match('/^(DELETE|UPDATE) /i', $statementString)) {
            $result = $this->sqlStatement->rowCount();
        }
        
        return $result;
    }
    
    public static function scopeSetObjectProperties (array &$scope, $jsonObject)
    {
        $properties = isset($scope['_properties']) ? $scope['_properties'] : array();
        
        foreach (get_object_vars($jsonObject) as $property=>$value) {
            $properties[$property] = $value;
        }
        
        $scope['_properties'] = $properties;
    }
}
?>
