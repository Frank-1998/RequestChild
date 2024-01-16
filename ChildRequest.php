<?php 
// include the Given file
include 'Request.php';

// Extend the Request class
class ChildRequest extends Request
{

    public function childProcess(string $uri): string
    {   
        $startTime = microtime(true);
        $response = $this -> process($uri);
        $timeSpent = microtime(true) - $startTime;
        return $response;
    }


}
?>