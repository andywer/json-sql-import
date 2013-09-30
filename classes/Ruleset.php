<?php
class Ruleset implements IRuleset
{
    private $importStatements;
    private $clearStatements = array();
    private $variables;
    private $insertedRows = 0;
    
    public function __construct (array $importStatements, array $variables)
    {
        $this->importStatements = $importStatements;
        $this->variables = $variables;
    }
    
    public function getImportStatements ()
    {
        return $this->importStatements;
    }
    
    public function getClearStatements ()
    {
        return $this->clearStatements;
    }
    
    public function setClearStatements (array $statements)
    {
        $this->clearStatements = $statements;
    }
    
    public function getVariables ()
    {
        return $this->variables;
    }
    
    public function getInsertedRowsCount ()
    {
        return $this->insertedRows;
    }
    
    public function import ($jsonObject, array $scope = null, &$insertedRows=0)
    {
        if ($scope) {
            $scope = array_merge($this->variables, $scope);
        } else {
            $scope = $this->variables;
        }
        
        RuleStatement::scopeSetObjectProperties($scope, $jsonObject);
        
        $insertedRows = 0;
        foreach ($this->importStatements as $statement) {
            $statement->execute($scope, $_insertedRows);
            $insertedRows += $_insertedRows;
        }
    }
    
    public function clear ()
    {
        $deletedRows = 0;
        foreach ($this->clearStatements as $statement) {
            $deletedRows += $statement->execute($this->variables);
        }
        
        return $deletedRows;
    }
}
?>
