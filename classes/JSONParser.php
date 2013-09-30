<?php
class JSONParser
{
    public function parseFile ($jsonFilePath)
    {
        $contents = file_get_contents($jsonFilePath);
        if (!$contents) {
            throw new Exception("Cannot read file: $jsonFilePath");
        }
        
        $parsed = $this->parseJson($contents);
        if (!$parsed) {
            throw new Exception("JSON decoding of '$jsonFilePath' failed: ".json_last_error());
        }
        
        return $parsed;
    }
    
    public function parseJson ($jsonString)
    {
        $jsonString = $this->removeComments($jsonString);
        
        return json_decode($jsonString);
    }
    
    private function removeComments ($jsonString)
    {
        while (preg_match('#^/\* .* \*/\n?#Ux', $jsonString, $matches)) {
            $jsonString = trim(str_replace($matches[0], "", $jsonString));
        }
        
        $jsonString = preg_replace('#//.*\n#Um', '', $jsonString);
        
        return $jsonString;
    }
}
?>
