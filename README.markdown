# BoxOfficeData

This PHP class extracts data from box office data websites [The Numbers](http://www.the-numbers.com) and [Box Office Mojo](http://www.boxofficemojo.com).

## Requirements

This class requires [Sean Hubers](http://github.com/shuber) [curl wrapper](http://github.com/shuber/curl).

## Demo

A demo of version 0.1 of this class can be seen [here](http://www.hamstar.co.nz/api/bod.php).  This release is currently at version 0.6 and as yet untested.

## Usage

### Initialization

To initialize this class you do the following.

	require_once 'class.bod.php';
	$bod = new BoxOfficeData;

### Searching for a movie

First of all a movie needs to be searched for.  This uses the search() method and requires a movie title argument. It can optionally include a year argument as well for more accurate searching.  The return value of search is an array of objects.

	$rs = $bod->search('District 9', 2009);
	print_r($rs);

This would output an array of result objects:

	Array
	(
	    [0] => stdClass Object
	        (
	            [title] => District 9
	            [year] => 2009
        	    [uid] => tn2009DIST9
	            [host] => www.the-numbers.com
        	)
	
	    [1] => stdClass Object
	        (
        	    [title] => Jazz in the Diamond District
	            [year] => 2009
        	    [uid] => tn20090JIDD
	            [host] => www.the-numbers.com
        	)
	
	)

(Please see also the compactResults(), ifl(), and forceHost() methods below)

### Result Objects

The results objects contain the title of the movie, the year the movie was released, a UID that can be passed to the getMovie() method, and the host from which the movie data came from.  You can print out the search results like so:

	foreach ( $rs as $r ) {
		echo 'The movie '. $r->title .', released in '. $r->year 
		    .'.  UID is '. $r->uid .'. Data from '. $r->host .'.';
	}

### Getting the data for a movie

To get the data for a movie the getMovie() method is used.  It takes either a UID from the search results, or the url of a movies details at The Numbers or Box Office Mojo.  It returns a single object containing the box office data.

	$movie = $bod->getMovie('tn2009DIST9');
	print_r($movie);

This would output a movie object:

	stdClass Object
	(
	    [budget] => 30000000
	    [gross] => 184373680
	    [year] => 2009
	    [title] => District 9
	    [profit] => 154373680
	    [url] => http://www.the-numbers.com/movies/2009/DIST9.php
	    [uid] => tn2009DIST9
	)

As you can see the production budget, gross revenue, and profit are listed, as well as other obvious data.

### The "I'm Feeling Lucky" method

A la google, the ifl() method returns a movie object and takes the same arguments as the search function.

	$movie = $bod->ifl('District 9', 2009);

This would output a movie object:

        stdClass Object
        (
            [budget] => 30000000
            [gross] => 184373680
            [year] => 2009
            [title] => District 9
            [profit] => 154373680
            [url] => http://www.the-numbers.com/movies/2009/DIST9.php
            [uid] => tn2009DIST9
        )

This is only as good as the search results coming from the box office data website.  Some movie names will need to be entered exactly as they appear on the site (such as x-men instead of xmen and spider-man instead of spiderman).

### Forcing results from one website only

The forceHost() method makes the class use only the site given in the arguments for getting the movie data from.  It can only take one of two arguments, 'the-numbers' and 'boxofficemojo'.  It is used as follows:

	$bod->forceHost('boxofficemojo');

### Killing dupes between sites

If multiple sites return the same movie results, the compactResults() method cuts out any dupes regardless of the site.  Because The Numbers is usually the first site searched, it will probably be the most prominent in the search results after calling this function. 

## Contact

Problems, comments and suggestions all welcome at [hamstar@telescum.co.nz](mailto:hamstar@telescum.co.nz)
