<?php
include 'ChildRequest.php';
use PHPUnit\Framework\TestCase;
class ChildRequestTest extends TestCase{

    public function testInit() {
        $childRequest = new ChildRequest(5);
        $this->assertSame(5, $childRequest->maxNumBins);
        echo "Object Init OK...\n";
    }

    public function testSetMaxNumBins() {
        $childRequest = new ChildRequest(5);
        $childRequest->setMaxNumBins(11);
        $this->assertSame(11, $childRequest->maxNumBins);
        echo "Set max number of bins OK...\n";
    }

    public function testChildProcess() {
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest = new ChildRequest(5);
        $response = $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri2);
        // test response message
        $this->assertSame("Sample response.", $response); 

        $reflection = new ReflectionClass($childRequest);
        $attribute = $reflection->getProperty('responseTimes');
        $attribute->setAccessible(true);
        $timeArray = $attribute->getValue($childRequest);
        // test time record method behavior
        $this->assertArrayHasKey($uri1, $timeArray);
        $this->assertArrayHasKey($uri2, $timeArray);
        $this->assertCount(2, $timeArray);
        $this->assertIsArray($timeArray[$uri1]);
        $this->assertCount(1, $timeArray[$uri1]);
        $this->assertIsArray($timeArray[$uri2]);
        $this->assertCount(1, $timeArray[$uri2]);
        echo "ChildProcess OK...\n";
    }

    public function testRetrieveMeanTime() {
        $childRequest = new ChildRequest(5);
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $reflection = new ReflectionClass($childRequest);
        $attribute = $reflection->getProperty('responseTimes');
        $attribute->setAccessible(true);
        $timeArray = $attribute->getValue($childRequest);
        $meanArray = $childRequest->retrieveMeanResTime();
        // testing behavior
        $this->assertCount(2, $meanArray);
        $this->assertArrayHasKey($uri1,$meanArray);
        $this->assertArrayHasKey($uri2,$meanArray);
        // --------------- ensure accruacy ---------------
        // when there is only record, mean is the same as raw time
        $this->assertSame($timeArray[$uri2][0], $meanArray[$uri2]);
        // if there are multiple records for the uri, calculate mean normally
        $this->assertSame($meanArray[$uri1], array_sum($timeArray[$uri1])/3);

        // ------------------- ensure stabality -------------
        $meanArray1 = $childRequest->retrieveMeanResTime();
        $meanArray2 = $childRequest->retrieveMeanResTime();
        $this->assertSame($meanArray1,$meanArray2);
        echo "Retrieve Mean Time OK...\n";
    }

    public function testRetrieveStdDev() {
        $childRequest = new ChildRequest(5);
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri2);
        $reflection = new ReflectionClass($childRequest);
        $attribute = $reflection->getProperty('responseTimes');
        $attribute->setAccessible(true);
        $timeArray = $attribute->getValue($childRequest);
        $results = $childRequest->retrieveStdDev();
        $result1 = $results[$uri1];
        $result2 = $results[$uri2];
        $answer = Stand_Deviation($timeArray[$uri1]);
        // ----------------------- testing behavior -----------------
        $this->assertArrayHasKey($uri1,$results);
        $this->assertArrayHasKey($uri2,$results);
        // ----------------------- ensuer accruacy ----------------
        $this->assertSame($result1,$answer);
        // test for cannot calculate std dev
        $this->assertSame(0.0, $result2); 

        // ----------------- ensure stability ------------------
        $results1 = $childRequest->retrieveStdDev();
        $results2 = $childRequest->retrieveStdDev();
        $this->assertSame($results1, $results2);
        echo "Retrieve Standard Deviation OK...\n";
    }

    public function testRetrieveHistogram() {
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest = new ChildRequest(5);
        $childRequest->childProcess('uri2');
        $childRequest->childProcess('uri2');
        $childRequest->childProcess('uri1');
        $childRequest->childProcess('uri1');
        $histogramArray = $childRequest->retrieveHistogram();
        // testing behavior
        $this->assertIsArray($histogramArray);
        $this->assertCount(2,$histogramArray);
        $this->assertArrayHasKey($uri1, $histogramArray);
        $this->assertArrayHasKey($uri2, $histogramArray);
        echo "Retrieve Histogram OK...\n";
    }

    public function testNormalizeData() {
        $childRequest = new ChildRequest(5);
        $reflection = new ReflectionClass($childRequest);
        $method = $reflection->getMethod("normalizeData");
        $method->setAccessible(true);
        $childRequest->childProcess('uri1');
        $childRequest->childProcess('uri1');
        $childRequest->childProcess('uri1');
        $childRequest->childProcess('uri1');
        $childRequest->childProcess('uri1');
        $attribute = $reflection->getProperty('responseTimes');
        $attribute->setAccessible(true);
        $timeArray = $attribute->getValue($childRequest);
        $result = $method->invoke($childRequest);
        // --------------- ensure behavior ----------------
        $this->assertIsArray($result);
        $this->assertArrayHasKey('uri1', $result);
        $this->assertCount(1, $result);
        $this->assertCount(5, $result['uri1']);
        // --------------- ensure accuracy -------------------
        $this->assertSame(zScoreNormalization($timeArray['uri1']),$result['uri1']);
        // ---------------- ensure stablility -----------------
        $childRequest->childProcess('uri2');
        $childRequest->childProcess('uri2');
        $childRequest->childProcess('uri2');
        $childRequest->childProcess('uri2');
        $childRequest->childProcess('uri2');
        $result1 = $method->invoke($childRequest);
        $result2 = $method->invoke($childRequest);
        $this->assertSame($result1,$result2);
        echo "Normalize data OK...\n";
    }

    public function testGenerateHistogramData() {
        $childRequest = new ChildRequest(5);
        $reflection = new ReflectionClass($childRequest);
        $method = $reflection->getMethod('generateHistogramData');
        $method->setAccessible(true);
        $maxBins = $childRequest->maxNumBins;
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest->childProcess($uri1); 
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri2);
        $result = $method->invoke($childRequest);
        $uri2Array = $result[$uri2];
        $keysOfUri2array = array_keys($uri2Array);
        $leftEdgeKey = $keysOfUri2array[0];
        $rightEdgeKey = $keysOfUri2array[4];
        $leftEdgeValue = $uri2Array[$leftEdgeKey];
        $rightEdgeValue = $uri2Array[$rightEdgeKey];
        $attribute = $reflection->getProperty('responseTimes');
        $attribute->setAccessible(true);
        $timeArray = $attribute->getValue($childRequest); 
        // ----------------- ensure behavior -----------------
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey($uri1,$result);
        $this->assertArrayHasKey($uri2,$result);
        $this->assertCount(1, $result[$uri1]);
        $this->assertCount($maxBins, $result[$uri2]);
        $this->assertArrayHasKey(strval($timeArray[$uri1][0]), $result[$uri1]);
        $this->assertTrue($leftEdgeValue > 0 && $rightEdgeValue > 0);
        // ------------------ ensure stablility -------------------
        $result1 = $method->invoke($childRequest);
        $result2 = $method->invoke($childRequest);
        $this->assertSame($result1, $result2);
        echo "Generate Histogram data OK...\n";
    }
}

// function to calculate the standard deviation 
// of array elements 
function Stand_Deviation($arr) 
{ 
    $num_of_elements = count($arr); 
      
    $variance = 0.0; 
      
            // calculating mean using array_sum() method 
    $average = array_sum($arr)/$num_of_elements; 
      
    foreach($arr as $i) 
    { 
        // sum of squares of differences between  
                    // all numbers and means. 
        $variance += pow(($i - $average), 2); 
    } 
      
    return (float)sqrt($variance/($num_of_elements-1)); 
} 


// by ChatGPT tested with online z-score calculator 
function zScoreNormalization($data) {
    // Calculate mean and standard deviation
    $mean = array_sum($data) / count($data);
    $stdDev = sqrt(array_sum(array_map(function($x) use ($mean) {
        return pow($x - $mean, 2);
    }, $data)) / (count($data)-1));
    // Z-score normalize the data
    $normalizedData = array_map(function($x) use ($mean, $stdDev) {
        return ($x - $mean) / $stdDev;
    }, $data);
    return $normalizedData;
}
?>