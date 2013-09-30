<?php
class TemplatedSQLStatement extends PDOStatement
{
    private $pdo;
    
    private $pdoStatement;
    
    private $statementString;
    
    private $evaluator;
    
    /** array( ':pXY' => '<expression to be evaluated>', ... ) */
    private $parameterBindings = array();
    
    
    public function __construct (PDO $pdo, $statementString)
    {
        $bindings = array();
        $statementString = self::prepareStatementString($statementString, $bindings);
        
        $this->pdo                  = $pdo;
        $this->pdoStatement         = $pdo->prepare($statementString);
        $this->evaluator            = new ExpressionEvaluator();
        $this->statementString      = $statementString;
        $this->parameterBindings    = $bindings;
    }
    
    public function getPDO ()
    {
        return $this->pdo;
    }
    
    public function getStatementString ()
    {
        return $this->statementString;
    }
    
    /**
     *  @param $scope
     *      Associative array to pass the variables and functions that shall
     *      be visible to the statement. The source object's properties should
     *      be set in $scope['_properties'].
     */
    public function execute ($scope=array())
    {
        if (APP::isInDebugMode()) {
            echo "Executing: " . $this->parseUsingScope($scope) . "\n";
        }
        
        foreach ($this->createParamBindings($scope) as $pdoParam=>$value) {
            $type = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $this->pdoStatement->bindValue($pdoParam, $value, $type);
        }
        
        return $this->pdoStatement->execute();
    }
    
    public function rowCount ()
    {
        return $this->pdoStatement->rowCount();
    }
    
    public function parseUsingScope (array $scope)
    {
        $bindings = $this->createParamBindings($scope);
        $string = $this->statementString;
        
        while (preg_match('/:p[0-9]+/', $string, $matches)) {
            $stmtParam = $matches[0];
            $boundValue = self::escapeValue($bindings[$stmtParam]);
            $string = str_replace($stmtParam, $boundValue, $string);
        }
        
        return $string;
    }
    
    private function createParamBindings ($scope)
    {
        $bindings = array();
        $this->evaluator->setScope($scope);
        
        foreach ($this->parameterBindings as $stmtParam=>$expression) {
            $bindings[$stmtParam] = $this->evaluator->evaluate($expression, $scope);
        }
        
        return $bindings;
    }
    
    private static function prepareStatementString ($statementString, array &$parameterBindings)
    {
        $parameterBindings = array();
        
        $statementString = self::replaceVariableProperties($statementString, $parameterBindings);
        $statementString = self::replaceProperties($statementString, $parameterBindings);
        $statementString = self::replaceVariables($statementString, $parameterBindings);
        $statementString = self::replaceCalls($statementString, $parameterBindings);
        
        return $statementString;
    }
    
    private static function replaceVariableProperties ($statementString, array &$parameterBindings)
    {
        return self::replace($statementString, '/\$[A-Za-z0-9_]+@[A-Za-z0-9_]+/', $parameterBindings);
    }
    
    private static function replaceProperties ($statementString, array &$parameterBindings)
    {
        return self::replace($statementString, '/@[A-Za-z0-9_]+/', $parameterBindings);
    }
    
    private static function replaceVariables ($statementString, array &$parameterBindings)
    {
        return self::replace($statementString, '/\$[A-Za-z0-9_]+/', $parameterBindings);
    }
    
    private static function replaceCalls ($statementString, array &$parameterBindings)
    {
        return self::replace($statementString, '/#\(.*\)/U', $parameterBindings);
    }
    
    private static function replace ($stmtString, $regex, array &$bindings)
    {
        $paramNo = self::getNextParamNo($bindings);
        
        while (preg_match($regex, $stmtString, $matches)) {
            $paramName = ":p{$paramNo}";
            $stmtString = str_replace($matches[0], $paramName, $stmtString);
            $bindings[$paramName] = $matches[0];
            $paramNo++;
        }
        
        return $stmtString;
    }
    
    private static function getNextParamNo ($bindings)
    {
        $maxParamNo = 0;
        foreach (array_keys($bindings) as $param) {
            $paramNo = intval( substr($param, 2) );
            $maxParamNo = max($paramNo, $maxParamNo);
        }
        
        return $maxParamNo + 1;
    }
    
    private static function escapeValue ($value)
    {
        if (is_string($value)) {
            return self::escapeString($value);
        } else {
            return $value;
        }
    }
    
    private static function escapeString ($string)
    {
        return "'" . str_replace("'", "\\'", $string) . "'";
    }
}
?>
