<?php 
// include the Given file
include 'Request.php';

// Extend the Request class
class ChildRequest extends Request
{
    // an array to record array of response times of each uri
    private $responseTimes = []; 
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
     * a method act as an exception thrower when trying to do somthing with an empty responseTimes array.
     * 
     * @param
     * @return array the responseTimes array.
     */
    private function getResponseTimeArray(): array{
        if (empty($this->responseTimes)){
            throw new Exception("There is no request yet.\n");
        }
        return $this->responseTimes;
    }

    /**
     * the getter to get maximum number of bins 
     * 
     * @param
     * @return int The maximum number of bins
     */
    public function getMaxNumBins(): int
    {
        return $this->maxNumBins;
    }
    
    /**
     * set the max bin number
     * 
     * @param int $num, the integer number of maximum bins
     * @return 
     */
    public function setMaxNumBins(int $num): void
    {
        $this->maxNumBins = $num;
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
     * Method to retrieve histogram for all responses(each URI separately)
     * Runtime: O(n)
     * 
     * @param 
     * @return array array contains key=>value pair of uri and image
     */
    public function retrieveHistogram(): array
    {
        try{
            $histogramData = $this->generateHistogramData();
        } catch(Exception $e){
            echo "Cannot generate histogram due to: ".$e->getMessage();
            return [];
        }
        // not enough uri to draw the histogram
        if (count($histogramData) == 0){
            return [];
        }
        // ##################### define image attribute ######################
        // --------------------- size ----------------
        $imgWidth = 450;
        $imgHeight = 300;
        $margins = 20; 
        $barWidth = 20;
        $graphWidth = $imgWidth - $margins * 2;
        $graphHeight = $imgHeight - $margins * 2;

        // ################### start to generating graph for each URI ##################
        $keys = array_keys($this->responseTimes);
        $imgArray = [];
        foreach($keys as $key){
            // create image
            $img = imagecreate($imgWidth, $imgHeight); 
            // define colors
            $barColor=imagecolorallocate($img,0,64,128);
            $backgroundColor=imagecolorallocate($img,240,240,255);
            $borderColor=imagecolorallocate($img,200,200,200);
            $lineColor=imagecolorallocate($img,220,220,220);
            // draw border and fill background color
            imagefilledrectangle($img,1,1,$imgWidth-2,$imgHeight-2,$borderColor); // draw border
            imagefilledrectangle($img,$margins,$margins,$imgWidth-1-$margins,$imgHeight-1-$margins,$backgroundColor); // fill background color
            
            $data = $histogramData[$key]; // data for each URI
            $numBars = count($data); // number of bars
            $gap = ($graphWidth-$numBars*$barWidth)/($numBars+1); // gap between bars

            // create scale for horizontal lines
            $maxValue = max($data);  
            $ratio = $graphHeight/$maxValue; 
            $horizontalLines = max($data);
            $horizontalGap = $graphHeight/$horizontalLines;
            // draw horizontal lines
            for($i = 1; $i <= $horizontalLines; $i++){
                $y=$imgHeight - $margins - $horizontalGap * $i;
                imageline($img, $margins, $y, $imgWidth-$margins, $y, $lineColor);
                $label = intval($horizontalGap * $i / $ratio);
                imagestring($img, 0, 5, $y-5, $label, $barColor);
            }
            // draw bars
            $xLabels = array_keys($data);
            for($i = 0; $i < $numBars; $i++){
                $xLabel = $xLabels[$i];
                $binData = $data[$xLabel];
                $x1 = $margins + $gap + $i * ($gap + $barWidth);
                $x2 = $x1 + $barWidth;
                $y1 = $margins + $graphHeight - intval($binData*$ratio);
                $y2 = $imgHeight - $margins;
                imagestring($img, 0, $x1+6, $y1-10, $binData, $barColor); // draw labels on the bins
                imagestring($img,0,$x1-20,$imgHeight-15,$xLabel,$barColor); // draw x-aixs lables
                imagefilledrectangle($img,$x1,$y1,$x2,$y2,$barColor); // color the bins
            }
            $imgArray[$key] = $img;
            // reset image to default state
            imagedestroy($img);

        }

        return $imgArray;
    } 

    /**
     * Generate histogram data from nomalized data
     * Runtime: O(n)
     * 
     * @param
     * @return array The histogram data(category and number of each category) of each URI 
     */
    private function generateHistogramData(): array
    {
        // if there is no normalized data, can't generate histogram data.
        try{
            $normalizedDataArrays = $this->normalizeData();
        } catch(Exception $e) {
            echo 'Cannot generate histogram data due to: '. $e->getMessage();
            return [];
        }
        $histogramData = [];
        foreach ($this->responseTimes as $key => $time){
            $normDataArray = $normalizedDataArrays[$key]; // get normalized data for this URI
            // # of unique data <= max bins, count each data's frequency and use data itself as label for x-aixs
            if(count(array_unique($normDataArray)) <= $this->maxNumBins){
                $normDataArray= array_map('strval', $normDataArray);
                $histogramData[$key] = array_count_values($normDataArray);
            } else { # of unique data > max bins, group data into evenly divided ranges, and count frequency
                $minValue = min($normDataArray);
                $maxValue = max($normDataArray);
                $binRange = ($maxValue-$minValue)/$this->maxNumBins;

                for($i = 1; $i <= $this->maxNumBins; $i++){
                    $rangeStart = $minValue;
                    $rangeEnd = $minValue + $binRange;
                    $histogramData[$key][strval(round($rangeStart,2))."~".strval(round($rangeEnd,2))] = $this->countInRange($rangeStart, $rangeEnd, $normDataArray);
                    $minValue += ($binRange+0.0000000000001); // increase minValue to prevent range overlap
                }
            }
        }
        return $histogramData;
    }

    /**
     * helper function of getHistogramData, count how many number from an array are in given range
     * Runtime: O(n)
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
        // if no record yet, can't normalize data, throw an error.
        if (empty($this->responseTimes)){
            throw new Exception("There is no requst yet.\n");
        }
        $normalizedData = [];
        $means = $this->retrieveMeanResTime(); // get means
        $stdDevs = $this->retrieveStdDev(); // get std devs
        foreach($this->responseTimes as $key=>$time){
            if (count($time) < 2){ // not enough data to normalize, return original data
                $normalizedData[$key][] = $time[0];
                continue;
            }
            // calculate normalized data
            $mean_i = $means[$key];
            $std_i = $stdDevs[$key];
            foreach($time as $data){
                $normalizedData[$key][]=($data - $mean_i)/$std_i;
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
        // if there no record yet, not be able to calculate mean, throw an error.
        try{
            $resTimeArr = $this->getResponseTimeArray();
        } catch(Exception $e){
            echo "Cannot calculate mean request time due to: ". $e->getMessage();
            return [];
        }
        $meanTimeArray = [];
        // calculate means
        foreach($resTimeArr as $key=>$times){
            $meanTimeArray[$key] = array_sum($times)/count($times);
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
    public function retrieveStdDev(): array
    {
        // there is no record yet, not able to calculate standard deviation, throw an error.
        try {
            $meanArray = $this->retrieveMeanResTime();
            $resTimeArr = $this->getResponseTimeArray();
        } catch(Exception $e) {
            echo 'Cannot calculate Standard Deviation due to: '.$e->getMessage()."\n";
            return [];
        }
        $stdDevArray = [];
        foreach($resTimeArr as $key => $times){
            $numData = count($times);
            // if there is only 1 request for this URI, std = 0, go to next URI
            if($numData == 1){
                $stdDevArray[$key] = 0.0;
                continue;
            }
            // calculate standard deviations
            $mean_i = $meanArray[$key];
            $sum = 0.0;
            foreach($times as $time){
                $sum += pow(($time - $mean_i), 2); 
            }
            $stdDev = sqrt($sum/($numData-1));
            $stdDevArray[$key] = $stdDev;
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