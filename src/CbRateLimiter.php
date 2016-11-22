<?php

namespace Gnikolovski;

/**
 * Couchbase Rate Limiter.
 *
 * Use this class to limit the number of requests comming from IP addresses.
 *
 * @author Goran Nikolovski <nikolovski84@gmail.com>
 * @website www.gorannikolovski.com
 */
class CbRateLimiter
{
    private $hostname;
    private $bucket;
    private $password;
    private $remaining;
    private $whitelisted = array();

    /**
    * Instantiate the class with Couchbase connection credentials.
    *
    * @param string $hostname
    * @param string $bucket
    * @param string $password
    */
    public function __construct($hostname, $bucket, $password = NULL)
    {
        $this->hostname = $hostname;
        $this->bucket = $bucket;
        $this->password = $password;
    }

    /**
    * Check if request limit has been exceeded.
    *
    * @param string $ip_address
    * @param int $max_requests
    * @param int $in_minutes
    */
    public function isExceeded($ip_address, $max_requests, $in_minutes)
    {
        if (in_array($ip_address, $this->whitelisted)) {
            $this->remaining = $max_requests;
            return FALSE;
        }

        $cluster = new \CouchbaseCluster($this->hostname);
        $bucket = $cluster->openBucket($this->bucket);
        try {
            $counter = $bucket->get($ip_address)->value;
            if ($counter < $max_requests) {
                $this->remaining = $max_requests - $counter;
                $bucket->counter($ip_address, 1);
                return FALSE;
            }
            $this->remaining = 0;
            return TRUE;
        } catch (\CouchbaseException $e) {
            $this->remaining = $max_requests;
            $bucket->counter($ip_address, 1, array('initial'=> 1, 'expiry' =>   $this->convertToSeconds($in_minutes)));
            return FALSE;
        }
    }

    /**
    * Convert minutes to seconds for setting Couchbase document time to live.
    *
    * @param string $minutes
    * @return int
    */
    public function convertToSeconds($minutes)
    {
        $seconds = $minutes*60;
        $seconds_in_month = 30*24*60*60;
        if ($seconds <= $seconds_in_month) {
            return $seconds;
        } else {
            return time() + $seconds;
        }
    }

    /**
    * Get remaining number of requests.
    *
    * @return int
    */
    public function getRemaining()
    {
        return $this->remaining;
    }

    /**
     * Add IP address to whitelist.
     *
     * @param string $ip_address
     */
    public function whitelist($ip_address)
    {
        $this->whitelisted[] = $ip_address;
    }
}
