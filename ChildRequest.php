<?php 
// include the Given file
include 'Request.php';

// Extend the Request class
class ChildRequest extends Request
{
    // an array to record array of response times of each uri
    private $responseTimes = [];


    // for testing and debugging purpose
    public function showTimeArray(): void
    {
        var_dump($this->responseTimes);
    }


    /**
     * Process the request and record response time
     * 
     * @param string $uri The URI of the request endpoint
     * @return string The response message
     */
    public function childProcess(string $uri): string
    {   
        // process the request and calculate the response time 
        $startTime = microtime(true);
        $response = $this -> process($uri);
        $timeSpent = microtime(true) - $startTime;

        $this->recordResponseTime($uri, $timeSpent); 

        return $response;
    }


    /**
     * retrive the mean time array, it contains mean response time for all url the object has processed so far
     * 
     * @param 
     * @return array The mean time array
     */
    public function retrieveMeanResTime(): array
    {
        $meanTimeArray = [];
        $keys = array_keys($this->responseTimes);
        foreach($keys as $key){
            $meanTimeArray[$key][] = array_sum($this->responseTimes[$key])/count($this->responseTimes[$key]);
        } 
        return $meanTimeArray;
    }


    /**
     * method to record one response time for a specific uri
     * 
     * @param string $uri The URI of the request endpoint
     * @return void
     */
    private function recordResponseTime(string $uri, float $responseTime): void
    {
        // if the uri is requested for the first time, init empty array for it
        if(!array_key_exists($uri, $this->responseTimes)){
            $this->responseTimes[$uri] = [];
        }

        $this->responseTimes[$uri][] = $responseTime;
    }
}
?>