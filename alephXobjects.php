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
	protected $findURL;
	protected $findXML;
	protected $presentURL;	
	protected $marc;
	
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
		$this->findXML = $this->alephXfind($this->findURL);
		$setNum = $this->alephXgetSetNum($this->findXML);
		$this->presentURL = $this->buildPresentURL($hostname, $path, $setNum);
		$this->marc = $this->alephXpresent($this->presentURL);
	} // end __construct
		
	// Build a url for use in the alephXfind() function. Requires a $request and a $code (i.e. "BAR")  
	protected function buildFindURL($hostname, $path, $request, $code, $base, $op = "find") {
		$findURL = $hostname.$path."request=".$request."&op=find&code=".$code."&base=".$base;
		return($findURL);
	} // end buildFindURL
	
	// Returns XML about a set of resuts. Requires the $findURL from buildFindURL()
	protected function alephXfind($findURL) {	
		$findResults = file_get_contents($findURL); // get results of find request
		$findXML = new SimpleXMLElement($findResults); // turn the results into an XML object
		return($findXML);	
	} // end alephXfind
	
	// Returns set number for a result set. Requires $findXML returned from alephXfind()
	protected function alephXgetSetNum($findXML) {			
		$setNum = $findXML->set_number;
		return($setNum);	
	} // end alephXgetSetNum
		
	// Build a url for use in the alephXpresent() function. Requires the $setNum returned by alephXgetSetNum()
	protected function buildPresentURL($hostname, $path, $setNum, $setEntry = 1) {
		$presentURL = $hostname.$path."set_no=".$setNum."&set_entry=".$setEntry."&op=present";
		return($presentURL);
	} // end buildPresentURL
	
	// Returns MarcXML for a record. Default is the first result of a set. Requires the $findURL from alephXbuildFindURL
	protected function alephXpresent($presentURL, $setEntry = 1){	
		$presentResults = file_get_contents($presentURL);
		$presentXML = new SimpleXMLElement($presentResults);
		return($presentXML); 
} // end alephPresent	
	
	// return MARC record for an object
	public function getMarc() {
		return($this->marc);
	} // end getMarc()
	
	// return findURL for an object
	public function getFindURL() {
		return($this->findURL);
	} // end getFindURL()
	
	// return presentURL for an object
	public function getPresentURL() {
		return($this->presentURL);
	} // end getPresentURL()
	
	// return findXML for an object
	public function getFindXML() {
		return($this->findXML);
	} // end getFindXML()
	
} // end AlephX object

$book = new AlephX("31430045584994", "barcode");
$book2 = new AlephX("004320251", "aleph");
$book3 = new AlephX("MCD28", "callnum");
//print_r($book3->presentURL);
print_r($book3->getFindXML());
echo "\n";

?>