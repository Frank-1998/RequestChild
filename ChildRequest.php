<?php 
// include the Given file
include 'Request.php';

// Extend the Request class
class ChildRequest extends Request
{
    // an array to record array of response times of each uri
    public $responseTimes = []; //TODO: method to retirve all uris (keys)
    public $maxNumBins;


    /**
     * the constructor for ChildRequest Class
     * 
     * @param int $maxNumBins the maximum number of bins for each histogram
     */
    function __construct(int $maxNumBins) 
    {
        $this->maxNumBins = $maxNumBins;   
    }

    /**
     * the getter to get maximum number of bins 
     * 
     * @param
     * @return int The maximum number of bins
     */
    public function get_maxNumBins(): int
    {
        return $this->maxNumBins;
    }


    // for testing and debugging purpose
    public function showTimeArray(): void
    {
        var_dump($this->responseTimes);
    }

    public function get_normalizedData(): void 
    {
        $result = $this->normalizeData();
        $keys = array_keys($result);
        foreach($keys as $key){
            sort($result[$key]);
        }
        var_dump($result);
    }
    public function get_histogramData(): void
    {
        var_dump($this->getHistogramData());
    }



    /**
     * Process the request and record response time
     * Runtime estimate: O(1)
     * 
     * @param string $uri The URI of the request endpoint
     * @return string The response message
     */
    public function childProcess(string $uri): string
    {   
        // process the request and calculate the response time 
        $startTime = microtime(true);
        $response = $this -> process($uri);
        $timeSpent = (microtime(true) - $startTime)*1000; // conver from seconds to milliseconds

        $this->recordResponseTime($uri, $timeSpent); 

        return $response;
    }

    /**
     * Generate histogram data from nomalized data
     * 
     * @param
     * @return array The histogram data(category and number of each category) of each URI 
     */
    private function getHistogramData(): array
    {
        
        $normalizedData = $this->normalizeData();
        // if there is no normalized data, can't generate histogram data.
        if (count($normalizedData) == 0){
            return [];
        }
        $histogramData = [];
        $keys = array_keys($this->responseTimes);
        foreach ($keys as $key){
            $data = $normalizedData[$key];
            $numOfUnique = count(array_unique($data));
            if ($numOfUnique <= $this->maxNumBins){
                $data = array_map('strval', $data);
                // there is less data than maximum bin number, # of bins < maximum bins number
                $histogramData[$key] = array_count_values($data);
            }
            else{ // divide the data into ranges(bins)
                $min_value = min($data);
                $max_value = max($data);
                $range = $max_value - $min_value;
                $binRange = $range/$this->maxNumBins;
                for ($i = 1; $i<=$this->maxNumBins; $i++){ // using max and min value to divide bins evenly
                    $start = $min_value;
                    $finish = $min_value + $binRange;
                    $histogramData[strval(round($start,2)."~".round($finish,2))] = $this->countInRange($start, $finish, $data);
                    $min_value += ($binRange+0.0000000000001);
                }
            }
        }
        return $histogramData;
    }

    /**
     * helper function of getHistogramData, count how many number from an array are in given range
     * 
     * @param float $min(range minimum), $max(range maximum), $numbers(array of number)
     * @return int how many numbers in the array are inside the range 
     */
    private function countInRange(float $min, float $max, array $numbers): int 
    {
        $result = 0; 
        foreach($numbers as $number){
            if ($number >= $min && $number <= $max){
                $result += 1;
            }
        }
        return $result;
    }



    /**
     * get the normalized version of $responseTimes, based on the information provided in Request.php (sleep time is generated with Gaussian distribution),
     * the normalization method is Z-score standardization
     * Runtime: O(n^2)
     * 
     * @param
     * @return array the Normalized version of $responseTimes, 
     */
    private function normalizeData(): array
    {
        // if no record yet, can't normalize data, return empty array.
        if (count($this->responseTimes) == 0){
            return [];
        }
        $normalizedData = [];
        $keys = array_keys($this->responseTimes); 
        $means = $this->retrieveMeanResTime();
        $stdDevs = $this->retrieveStdDev();

        foreach($keys as $key){
            $data = $this->responseTimes[$key];
            // if there is only 1 data, can't normalize it, continue to next URI
            if (count($data) < 2){
                $normalizedData[$key][] = $data[0];
                continue;
            }
            $mean_i = $means[$key][0];
            $std_i = $stdDevs[$key][0];
            foreach($data as $time){
                $normalizedTime = ($time - $mean_i)/$std_i;
                $normalizedData[$key][] = $normalizedTime;
            }
        }
        return $normalizedData;
    }


    /**
     * retrive the mean time array, it contains mean response time for all url the object has processed so far
     * Runtime: O(n)
     * 
     * @param 
     * @return array The mean time array
     */
    public function retrieveMeanResTime(): array
    {
        $meanTimeArray = [];
        // if there no record yet, not be able to calculate mean, return empty array.
        if (count($this->responseTimes) == 0){
            return [];
        }
        $keys = array_keys($this->responseTimes);
        foreach($keys as $key){
            $meanTimeArray[$key][] = array_sum($this->responseTimes[$key])/count($this->responseTimes[$key]);
        } 
        return $meanTimeArray;
    }

    /**
     * Retrieve the standard deviation of each URI
     * Runtime: O(n^2)
     * 
     * @param 
     * @return array The array that contains a standard deviation of each URI
     */
    // TODO: implement error handling
    public function retrieveStdDev(): array
    {
        $meanArray = $this->retrieveMeanResTime();
        // there is no record yet, not able to calculate standard deviation, return empty array.
        if (count($meanArray) == 0){
            return [];
        }
        $stdDevArray = [];
        $keys = array_keys($this->responseTimes);
        foreach($keys as $key){
            $sum = 0.0; // for summation
            $num_data = count($this->responseTimes[$key]); // n of each URI
            // if there is only 1 request for this URI, the standard deviation is 0, continue to the next URI
            if ($num_data == 1){
                $stdDevArray[$key][] = 0.0;
                continue;
            }
            $mean_i = $meanArray[$key][0]; // mean of each URI

            // to calculate the sum of square of the (x - x_mean)
            foreach($this->responseTimes[$key] as $time){
                $sum += pow($time - $mean_i,2);
            }
            $stdDev = sqrt($sum/($num_data-1)); // calculate std dev
            $stdDevArray[$key][] = $stdDev;
        }
        return $stdDevArray;
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