<?php

// build an AlephX object. I don't know yet what it will become.
class AlephX {
	protected $hostname = "http://catalog.umd.edu";
	protected $path = "/X?";
	protected $base = "CP";
	protected $alephcodes = array (
		"barcode" => "BAR",
		"oclc" => "035",
		"aleph" => "SYS",
		"callnum" => "CNL", // note, this is for non-LC call nums
		"isbn" => "020",
		);
	// To begin, allow AlephX objects to be created for given types: "barcode", "oclc", "aleph", "callnum", "isbn"	
	public function __construct($request, $type) {
		$hostname = $this->hostname;
		$path = $this->path;
		$base = $this->base;	
		// unnecessary $this->request = $request;
		foreach ($this->alephcodes as $key => $value) {
			if ($key == $type) {
				$code = $value;
			} // end if
		} // end foreach
		$this->findURL = $this->buildFindURL($hostname, $path, $request, $code, $base);	
		
	} // end __construct
		
	// Build a url for use in the alephXfind() function. Requires a $request and a $code (i.e. "BAR")  
	protected function buildFindURL($hostname, $path, $request, $code, $base, $op = "find") {
		$findURL = $hostname.$path."request=".$request."&op=find&code=".$code."&base=".$base;
		return($findURL);
	} // end buildFindURL
	
	
} // end AlephX object

$book = new AlephX("31430045584994", "barcode");
echo $book->findURL;

// Build a url for use in the alephXfind() function. Requires a $request and a $code (i.e. "BAR")  
function alephXbuildFindURL($request, $code, $base = "CP", $op = "find") {
	$findURL = "http://catalog.umd.edu/X?request=".$request."&op=".$op."&code=".$code."&base=".$base;
	return($findURL);
}

// Build a url for use in the alephXpresent() function. Requires the $setNum returned by alephXgetSetNum()
function alephXbuildPresentURL($setNum, $setEntry = 1) {
	$presentURL = "http://catalog.umd.edu/X?set_no=".$setNum."&set_entry=".$setEntry."&op=present";
	return($presentURL);
}

// Returns XML about a set of resuts. Requires the $findURL from alephXbuildFindURL
function alephXfind($findURL) {	
	$findResults = file_get_contents($findURL); // get results of find request
	$findXML = new SimpleXMLElement($findResults); // turn the results into an XML object
	return($findXML);	
} // end alephFind

?>