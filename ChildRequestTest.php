<?php
include 'ChildRequest.php';
use PHPUnit\Framework\TestCase;
class ChildRequestTest extends TestCase{

    public function testInit() {
        // test instantiating the object
        $childRequest = new ChildRequest(5);
        $this->assertSame(5, $childRequest->maxNumBins);
    }

    public function testSetMaxNumBins() {
        // test setMaxNumBins
        $childRequest = new ChildRequest(5);
        $childRequest->setMaxNumBins(11);
        $this->assertSame(11, $childRequest->maxNumBins);
    }

    public function testChildProcess() {
        // test child process, and ability to record times
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest = new ChildRequest(5);
        $response = $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri2);
        // test response message
        $this->assertSame("Sample response.", $response); 

        $reflection = new ReflectionClass($childRequest);
        $property = $reflection->getProperty('responseTimes');
        $property->setAccessible(true);
        $timeArray = $property->getValue($childRequest);
        // test time record method behavior
        $this->assertArrayHasKey($uri1, $timeArray);
        $this->assertArrayHasKey($uri2, $timeArray);
        $this->assertCount(2, $timeArray);
        $this->assertIsArray($timeArray[$uri1]);
        $this->assertCount(1, $timeArray[$uri1]);
        $this->assertIsArray($timeArray[$uri2]);
        $this->assertCount(1, $timeArray[$uri2]);
    }

    public function testRetrieveMeanTime() {
        // test retrieve mean time method.
        $childRequest = new ChildRequest(5);
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest->childProcess($uri2);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $reflection = new ReflectionClass($childRequest);
        $property = $reflection->getProperty('responseTimes');
        $property->setAccessible(true);
        $timeArray = $property->getValue($childRequest);
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
    }

    public function testRetrieveStdDev() {
        // test retrieve standard deviation
        $childRequest = new ChildRequest(5);
        $uri1 = 'uri1';
        $uri2 = 'uri2';
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri1);
        $childRequest->childProcess($uri2);
        $reflection = new ReflectionClass($childRequest);
        $property = $reflection->getProperty('responseTimes');
        $property->setAccessible(true);
        $timeArray = $property->getValue($childRequest);
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
    }

    public function testRetrieveHistogram() {
        // test retrieve histogram
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

?>