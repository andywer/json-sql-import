<?php
interface IRuleset
{
    public function import ($jsonObject, array $scope = null, &$insertedRows=0);
    
    public function clear ();
}
?>
