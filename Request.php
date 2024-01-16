<?php
/**
* Resource request processing class
*
* Instantiations of this class do state based processing of resource requests .
* To use , instantiate an object and call process () on a URI to get the response
* data. Children of this class can augment functionality by overriding start ()
* and finish ().
*/
class Request
{
    /**
    * Means for simulated response latencies
    *
    * Let ’s pretend that this doesn ’t actually exist in this class. Unit are
    * microseconds .
    */
    private const MEANS = [
        'uri1 ' => 10000 , 
        'uri2 ' => 20000];
    /**
    * The default mean latency in microseconds
    *
    * Let ’s pretend that this doesn ’t actually exist in this class.
    */
    private const DEFAULT_MEAN = 15000;
    /**
    * Standard deviations for simulated response latencies
    *
    * Let ’s pretend that this doesn ’t actually exist in this class. Unit are
    * microseconds .
    */
    private const STDDEVS = [
    'uri1 ' => 2500 ,
    'uri2 ' => 7500];
    /**
    * The default standard deviation for latencies in microseconds
    *
    * Let ’s pretend that this doesn ’t actually exist in this class.
    */
    private const DEFAULT_STDDEV = 5000;
    /**
    * Simulate a delay that is specific to the URI in question
    *
    * Let ’s pretend that this function doesn ’t actually exist in this class
    *
    * @param string $uri The URI of the request endpoint
    */
    private static function simulateLatency (string $uri ): void
    {
    // The following puts execution to sleep by a random amount of
    // microseconds . This amount is generated by transforming PHP ’s uniform
    // random number generation into Gaussian random number generation via
    // the Box -Muller transformation .
        $responseTime = round(
            sqrt( -2.0 * log(mt_rand(
            PHP_FLOAT_EPSILON *mt_getrandmax(),
            mt_getrandmax())/mt_getrandmax()))
            * (self :: STDDEVS[$uri] ?? self :: DEFAULT_STDDEV )
            * cos(2*pi()*mt_rand()/mt_getrandmax())
            + (self :: MEANS[$uri] ?? self :: DEFAULT_MEAN ));
        if($responseTime >= 1)
            usleep($responseTime );
    }
    /**
    * Start processing the request in the child class
    *
    * @param string $uri The URI of the request endpoint
    */
    protected function start(string $uri ): void
    {
    // Base class version does nothing
    }

    /** Finish processing the request in the child class */
    protected function finish (): void
    {
        // Base class version does nothing
    }
    /**
    * Process the request
    *
    * @param string $uri The URI of the request endpoint
    * @return string The response data
    */
    final public function process(string $uri ): string
    {
        $this ->start($uri );
        // Let ’s pretend the following line is doing something instead of just
        // simulating response latency
        self :: simulateLatency ($uri );
        $this ->finish ();
        return 'Sample response.';
    }
}
?>