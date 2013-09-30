<?php
class RulesetGroup implements IRuleset
{
    private $name;
    private $rulesets;
    private $rulesetParser;
    
    public static function exists ($groupName)
    {
        return is_dir( self::getRulesetDirPath() . "/$groupName" );
    }
    
    public static function open ($groupName, RulesetParser $rulesetParser)
    {
        return new RulesetGroup($groupName, $rulesetParser);
    }
    
    private function __construct ($groupName, RulesetParser $rulesetParser)
    {
        if (!self::exists($groupName)) {
            throw new Exception("Ruleset group not found: $groupName");
        }
        
        $this->name = $groupName;
        $this->rulesetParser = $rulesetParser;
        $this->rulesets = $this->loadRulesets();
    }
    
    public function import ($jsonObject, array $scope = null, &$insertedRows=0)
    {
        foreach ($this->rulesets as $ruleset) {
            $ruleset->import($jsonObject, $scope, $_insertedRows);
            $insertedRows += $_insertedRows;
        }
    }
    
    public function clear ()
    {
        $deletedRows = 0;
        
        foreach ($this->rulesets as $ruleset) {
            $deletedRows += $ruleset->clear();
        }
        
        return $deletedRows;
    }
    
    private function loadRulesets ()
    {
        $rulesets = array();
        $dir = dir(self::getRulesetDirPath() . "/" . $this->name);
        
        while ( $entry = $dir->read() ) {
            if (strtolower(substr($entry, -5)) != ".json") {
                continue;
            }
            
            $filePath   = $dir->path . "/" . $entry;
            $rulesets[] = $this->rulesetParser->parseRulesetFile($filePath);
        }
        
        $dir->close();
        return $rulesets;
    }
    
    private static function getRulesetDirPath ()
    {
        return dirname(__FILE__) . "/../rulesets";
    }
}
?>
