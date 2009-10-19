<?php

	// Include the curl library
	require 'curl.php';

	/**
	* Class to search sites that have box office data
	* and return the box office data and profit data
	* for a given movie.
	*
	* @author    Robert Mcleod
	* @date	     18 October 2009
	* @copyright 2009 Robert McLeod
	* @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
	* @link      http://github.com/hamstar/BoxOfficeData
	* @version   0.6
	*/
	class BoxOfficeData {

		// Sets the search URLs for the various sites that have box office data on them
		private $searchURLs = array(
			'http://www.the-numbers.com/interactive/search/doSearch.php?sp={TITLE}&area=Movies',
			'http://www.boxofficemojo.com/search/?q={TITLE}'
		);
		
		// Host identifier array for building URLs
		private $hi = array(
			'tn' => 'http://www.the-numbers.com/movies/{y}/{p}.php',
			'bm' => 'http://www.boxofficemojo.com/movies/?id={p}.htm'
		);

		/**
		* Constructor
		*
		*/
		function __construct() {}
		
		/**
		* Sets the regex patterns to be searched for in the HTML
		* depending on the last host curled by the getHTML() method
		*
		* @return bool
		*/
		private function setPatterns() {
		
			switch ($this->host) {
			case 'www.the-numbers.com':
				$this->P_title = '@<h2 align="CENTER">(.+)</h2>@i';
				$this->P_year = '@<TR><TD VALIGN="TOP"><B>Released</B></TD>\r\n<TD>\r\n\w+ \d+, (\d+)@';
				$this->P_budget = '@<TD><B>Production Budget</B></TD>\s+<TD>\$([0-9,]+)</TD>@';
				$this->P_gross = '@<TD><B>Worldwide Gross</B></TD>\s+<TD align="right">\$([0-9,]+)</TD>@';
				$this->P_searches = array(
					'@<P><B><A HREF="/movies/(?<year>'.$this->year.')/(?<path>\w+)\.php">(?<title>.+) - Box Office Data, Movie News, Cast Information - The Numbers</A></B>@',
					'@<P><B><A HREF="/movies/(?<year>'.$this->year.')/(?<path>\w+)\.php">Movie (?<title>.+) - Box Office Data, News, Cast Information - The Numbers</A></B>@'
				);
				return true;
				break;
			
			case 'www.boxofficemojo.com':
				$this->P_title = '@<title>(.+) \(\d+\) - Box Office Mojo</title>@';
				$this->P_year = '@<title>.+ \((\d+)\) - Box Office Mojo</title>@';
				$this->P_budget = '@<td>Production Budget: <b>([0-9,]+)</b>@';
				$this->P_gross = '@<td width="40%">=&nbsp;Worldwide:</td>\n\t\t\t\t<td width="35%" align="right"><b>&nbsp;$([0-9,]+)</td>@';
				$this->P_searches = array(
					'@<td><b><font face="Verdana" size="2"><a href="/movies/\?id=(?<path>.+).htm">(?<title>.+)</a></font></b></td>\n\t<td align="right"><font face="Verdana" size="2"><a href="/schedule/\?view=bydate&release=theatrical&date=2007-01-12&p=.htm">1/12/(?<year>'.$this->year.')</a></font></td>@'
				);
				return true;
				break;
			}
		
		}
		
		/**
		* Generates a UID from the year and path given, and the
		* last host curled.
		*
		* @param integer $year Year of movie
		* @param string $path Unique identifier at the host
		*
		* @return string
		*/
		private function generateUid($year, $path) {
		
			// Switch depending on host
			switch ($this->host) {
			case 'www.the-numbers.com':
				$h = 'tn';
				break;
			case 'www.boxofficemojo.com':
				$h = 'bm';
				break;
			}
			
			// Return the UID
			return $h.$year.$path;
		
		}
		
		/**
		* Generates a URL from a UID
		*
		* @param string $uid UID of the movie to generate the URL for
		*
		* @return string
		*/
		private function generateUrl($uid) {
		
			// If we are given a URL give it back straight away
			if (substr($uid, 0, 6) == 'http://') {
				return $uid;
			}
		
			// Break up the UID
			$h = substr($uid, 0, 2);
			$y = substr($uid, 2, 4);
			$p = substr($uid, 6);

			// Generate the URL and return it
			return str_replace(array('{y}','{p}'), array($y, $p), $this->hi[$h]);
		
		}
		
		/**
		* Allows a user to force using a known host
		* Called without the host argument resets to use any host
		*
		* @param string $host The name of the host to be used
		*
		* @return bool
		*/ 
		public function forceHost($host = false) {
			
			// No host specified - reset the variable
			if($host === false) {
				$this->force = false;
				return true;
			}
			
			// Check that we know the host given
			if( $host != 'the-numbers' && $host != 'boxofficemojo' ) {
				die('Don\'t know that host!');
			}
			
			// All good, force this host
			$this->force = $host;
			return true;
		}
		
		/**
		* Get the results from a search and saves them
		* to the results variable
		*
		* @return void
		*/
		private function getResults() {

			// Check that there is html to match against
			if(!$this->html) {
				// No HTML data to get results from
				die('No HTML to get results from in getResults()');
			}

			// Set up the matches array
			$matches = array();
			
			// Run through all the search strings to get the movie
			// URL, title and year released
			foreach($this->P_searches as $p) {
				if(preg_match_all($p, $this->html, $m, PREG_SET_ORDER)) {
					
					// merge with the main matches array
					$matches = array_merge($matches, $m);
				}
			}
			
			// Append the results array with the match data in an object
			foreach($matches as $m) {
				// Start the Result object
				$r = new StdClass;
				
				// Assign the data
				$r->title = $m['title'];
				$r->year = $m['year'];
				$r->uid = $this->generateUid($r->year, $m['path']);
				$r->host = $this->host;
				
				// Chuck it in the array
				$this->results[] = $r;
				
				// Kill the object
				$r = null;
			}
			
		}

		/**
		* Compacts results so that there are no dupes of movies
		* across sites
		*
		*/
		public function compactResults() {
		
			// Make a local array
			$results = $this->results;
		
			// Run through the master array
			foreach($this->results as $_K => $_R) {
				// Unset this key
				$results[$_K] = null;
				
				// Assign the title and year
				$year = $_R->year;
				$title = $_R->title;
				
				// Compare against the local array
				foreach($results as $k => $r) {
					
					// Make sure there is an object
					if(is_object($r)) {
					
						// Check if it is a dupe
						if($r->title = $title && $r->year == $year) {
							
							// If it is a dupe unset it from the class array
							unset($this->results[$k]);
						
						} // end dupe check
					
					} // end object check
				
				} // end nested foreach
			
			} // end main foreach
		
		}

		/**
		* Gets the details for a movie and returns an object for that movie
		*
		* @return object
		*/
		private function getDetails() {
		
			// Check that there is html to match against
			if(!$this->html) {
				// No HTML data to get details from
				die('No HTML data to get details from in getDetails()');
			}
			
			// Fire up the object
			$obj = new StdClass;
			
			// Search for our data with regexes!
			$obj->budget = (preg_match($this->P_budget, $this->html, $m))
				? str_replace(',','',$m[1])
				: 0;
			
			$obj->gross = (preg_match($this->P_gross, $this->html, $m))
				? str_replace(',','',$m[1])
				: 0;
			
			$obj->year = (preg_match($this->P_year, $this->html, $m))
				? $m[1]
				: 0;
			
			$obj->title = (preg_match($this->P_title, $this->html, $m))
				? $m[1]
				: 'Unknown';
			
			// Drop the object
			return $obj;
			
		}

		/**
		* Gets a pages HTML and assigns it to the html variable
		* Also sets the host so the above methods cannot be called
		* before this method.
		*
		* @param string $url URL to get the HTML from
		*
		* @return void
		*/
		private function getHTML($url) {
			
			// Get the host of this URL
			$host = parse_url($url, PHP_URL_HOST);
			
			// Die quietly if we aren't supposed to use this host
			if($this->force && !strstr($host, $this->force)) {
				return false;
			}
			
			// Reset the HTML and host for this curl
			$this->html = null;
			
			// Get the HTML data
			$c = new Curl;
			$c->useragent = 'Mozilla/4.0 (BoxOfficeDataBot)';
			$this->html = $c->get($url)->body;
			
			// Kill Curl
			$c = null;
			
			// Set the host
			$this->host = $host;
			
			// Set the patterns
			$this->setPatterns();
			
		}

		/**
		* Searches for the movie across all known sites
		* and returns an array of associative arrays
		*
		* @param string $title Title of the movie to search for
		* @param integer $year Year the movie was released
		*
		* @return array
		*/
		public function search($title, $year = false) {
		
			// Set the year of the search or the regex digit for all
			$this->year = (is_numeric($year)) ? $year : '\d+';

			// Reset the results variable
			$this->results = null;
			
			// Run through each search url for each site we know of
			foreach ($this->searchURLs as $url) {
				
				// Put the title in this search URL
				$url = str_replace('{TITLE}', urlencode($title), $url);
			
				// Get the HTML
				$this->getHTML($url);

				// Get the results
				$this->getResults();
			}
			
			// If there were results return them
			if($this->results) {
				return $this->results;
			}
			
			// No results!
			die('No results from url: '.$url);
			
		}
		
		/**
		* Gets the details of a movie from the given URL
		*
		* @param string $uid URL of the movie details
		*
		* @return object
		*/
		public function getMovie($uid) {
			
			// Build a URL if we get the UID
			$url = $this->generateUrl($uid);
			
			// Get the HTML for this movie
			$this->getHTML($url);
			
			// Get the movie details
			$movie = $this->getDetails();
			
			// Set the profit and URL
			$movie->profit = $movie->gross - $movie->budget;
			$movie->url = $url;
			$movie->uid = $uid;
			
			// Return the movie
			return $movie;
			
		}
		
		/**
		* I'm feeling lucky method - returns the first movie
		* from the search results
		*
		* @param string $title Title of movie
		* @param string $year Year of movie
		*
		* @return object
		*/
		public function ifl($title, $year = false) {
		
			// Search for the movie
			$this->search($title, $year);
			
			// Drop the first result
			return $this->getMovie($this->results[0]->uid);
		
		}
	}
	
?>