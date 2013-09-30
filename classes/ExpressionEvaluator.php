<?php
class ExpressionEvaluator
{
    private $scope = array( '_properties' => array() );
    
    
    public function __construct (array $scope = null)
    {
        if ($scope) {
            $this->setScope($scope);
        }
    }
    
    /**
     *  @param $scope
     *      Associative array to pass the variables and functions that shall
     *      be visible to the statement. The source object's properties should
     *      be set in $scope['_properties'].
     */
    public function setScope (array $scope)
    {
        $this->scope = $scope;
    }
    
    public function evaluate ($expression)
    {
        if ($expression[0] == '@') {
            return $this->evaluateProperty($expression);
        } else if ($expression[0] == '$') {
            $identifiers = explode('@', $expression);
            if (count($identifiers) == 1) {
                return $this->evaluateVariable($expression);
            } else {
                return $this->evaluateVariableProperty($identifiers[0], $identifiers[1]);
            }
        } else if (substr($expression, 0, 2) == "#(") {
            return $this->evaluateFunctionCall($expression);
        } else {
            throw new Exception("Unable to evaluate expression: $expression");
        }
    }
    
    private function evaluateProperty ($expression)
    {
        $name = substr($expression, 1);
        $properties = $this->scope['_properties'];
        
        if (!isset($properties[$name])) {
            throw new Exception("Undefined property: @$name");
        }
        
        return $properties[$name];
    }
    
    private function evaluateVariable ($expression)
    {
        $name = substr($expression, 1);
        
        if (!isset($this->scope[$name])) {
            throw new Exception("Undefined variable: \$$name");
        }
        
        return $this->scope[$name];
    }
    
    private function evaluateVariableProperty ($varName, $propName)
    {
        if ($varName[0] == '$') {
            $varName = substr($varName, 1);
        }
        
        if (!isset($this->scope[$varName])) {
            throw new Exception("Undefined variable: \$$varName");
        }
        
        $object = $this->scope[$varName];
        if (!is_object($object)) {
            throw new Exception("Variable is not an object: $varName");
        }
        if (!isset($object->{$propName})) {
            throw new Exception("Object does not contain a property '$propName': \$$varName");
        }
        
        return $object->{$propName};
    }
    
    private function evaluateFunctionCall ($expression)
    {
        if (!preg_match('/^([#A-Za-z0-9\._-]+)\((.*)\)$/', $expression, $matches)) {
            throw new Exception("Cannot evaluate function call: $expression");
        }
        
        $function = $matches[1];
        $parameters = explode(",", $matches[2]);
        
        array_walk($parameters, function(&$parameter)
        {
            $parameter = trim($parameter);
        });
        
        return $this->evaluateParsedFunctionCall($function, $parameters);
    }
    
    private function evaluateParsedFunctionCall ($function, array $parameters)
    {
        if (! $function == "#") {
            throw new Exception("Undefined function called: $function");
        }
        
        if(count($parameters) != 1) {
            throw new Exception("Wrong number of parameters passed to $function: (" . implode(", ", $parameters) . ")");
        }
        
        $paramString = $parameters[0];
        
        if ($paramString[0] == $paramString[strlen($paramString)-1] && ($paramString[0] == '"' || $paramString[0] == "'")) {
            $tableName = substr($paramString, 1, -2);
        } else {
            throw new Exception("Invalid string parameter passed: $paramString");
        }
        
        return $tableName;
    }
}
?>
