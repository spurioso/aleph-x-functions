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
	protected $alephNum;
	protected $itemDataURL;
	protected $itemData;
	
	// To begin, allow AlephX objects to be created for given types: "barcode", "oclc", "aleph", "callnum", "isbn"	
	public function __construct($request, $type) {			
		
		foreach ($this->alephcodes as $key => $value) {
			if ($key == $type) {
				$code = $value;
			} // end if
		} // end foreach
		if ($code == "035") {
			$request = $this->OCLCforAlephX($request);
		}
		$this->findURL = $this->buildFindURL($request, $code);	
		$this->findXML = $this->alephXfind($this->findURL);
		$setNum = $this->getSetNum($this->findXML);
		$this->presentURL = $this->buildPresentURL($setNum);
		$this->marc = $this->alephXpresent($this->presentURL);
		$this->alephNum = $this->getAlephNum();
		$this->itemDataURL = $this->buildItemDataURL($this->alephNum);
		$this->itemData = $this->alephXitemData($this->itemDataURL);
	} // end __construct
		
	// Build a url for use in the alephXfind() function. Requires a $request and a $code (i.e. "BAR")  
	protected function buildFindURL($request, $code) {
		$hostname = $this->hostname;
		$path = $this->path;
		$base = $this->base;
		$op = "find";	
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
	protected function getSetNum($findXML) {			
		$setNum = $findXML->set_number;
		return($setNum);	
	} // end getSetNum
	
	// Tests to see if a set number was returned (i.e. was the search successful)
	protected function testForSetNum($findXML) {		
		if ($findXML->set_number) {		
			return (True);
		} else {
			return(False);
		} // end if	
	} // end testForSetNum	
		
	// Build a url for use in the alephXpresent() function. Requires the $setNum returned by getSetNum()
	protected function buildPresentURL($setNum, $setEntry = 1) {
		$hostname = $this->hostname;
		$path = $this->path;	
		$presentURL = $hostname.$path."set_no=".$setNum."&set_entry=".$setEntry."&op=present";
		return($presentURL);
	} // end buildPresentURL
	
	// Returns MarcXML for a record. Default is the first result of a set. Requires the $findURL from alephXbuildFindURL
	protected function alephXpresent($presentURL, $setEntry = 1){	
		$presentResults = file_get_contents($presentURL);
		$presentXML = new SimpleXMLElement($presentResults);
		return($presentXML); 
	} // end alephPresent
	
	// Build a url for use in the alephXitemData() function. Requires an Aleph system number ($docNumber)
	protected function buildItemDataURL($docNumber) {
		$hostname = $this->hostname;
		$path = $this->path;
		$base = $this->base;		
		$itemDataURL = $hostname.$path."op=item_data&doc_number=".$docNumber."&base=".$base;
		return ($itemDataURL);	
	} // end buildItemDataURL
	
	// Returns item data. Requires itemDataURL from buildItemDataURL()
	protected function alephXitemData($itemDataURL){				
		$itemDataResults = file_get_contents($itemDataURL);
		$itemDataXML = new SimpleXMLElement($itemDataResults);	
		return($itemDataXML); 
	} // end alephItemData
			
	// OCLC numbers have to be 8 digits for sending to Aleph. If less than 8, add zeroes.
	protected function OCLCpadNum($oclc) {
		if (strlen($oclc) < 8) {
			if (strlen($oclc) == 6) {
				$oclcPad = "00".$oclc;			
			} elseif (strlen($oclc) == 7) {
				$oclcPad = "0".$oclc;			
			} // end if		
		} else {
			$oclcPad = $oclc;
		} // end if
	return($oclcPad);
	} // end OCLCpadNum
	
		// OCLC nums sent to aleph need to be preceded by "ocn" or "ocm". 
	protected function OCLCtestPrefix($oclcPad) {
		$prefix = "ocm";		
		$oclcPre = $prefix.$oclcPad;	
		$findURL = $this->buildFindURL($oclcPre, "035");
		$findXML = $this->alephXfind($findURL);
		if ($test = $this->testForSetNum($findXML)) {
			return($oclcPre);		
		} else {			
			$prefix = "ocn";		
			$oclcPre = $prefix.$oclcPad;
			$findURL = $this->buildFindURL($oclcPre, "035");
			$findXML = $this->alephXfind($findURL);		
			if ($test = $this->testForSetNum($findXML)) {
				return($oclcPre);			
			} // end if
		} // end if
	} // end OCLCtestPrefix
	
	// Prepares OCLC number for Aleph-X-services. Pads OCLC nums shorter than 8 digits, and adds proper prefix.
	protected function OCLCforAlephX($oclc) {
		$oclcPad = $this->OCLCpadNum($oclc);	
		$oclcForAlephX = $this->OCLCtestPrefix($oclcPad);	
		return($oclcForAlephX);
	} // end OCLCforAlephx


	// PUBLIC METHODS
	
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
	
	// Returns an Aleph system number	
	public function getAlephNum() {	
		$sysNum = $this->marc->record->doc_number;
	return($sysNum);

	} // end getSetNum
	
	// Retrieves Aleph item data (call number, locations, etc)
	public function getItemData() {
		$itemData = $this->itemData;
		return($itemData);
	} // end getItemData	
	
} // end AlephX object

$book1 = new AlephX("31430045584994", "barcode");
$book2 = new AlephX("004320251", "aleph");
$book3 = new AlephX("MCD28", "callnum");

// oclcnums for testing. one is 7 digits. I think one of the others requires "ocn" prefix, others require "ocm" prefix
$oclcNums = array ("2648489", "173136007", "428436794", "34919814");

$book4 = new AlephX($oclcNums[3], "oclc");
$book5 = new AlephX("9780596100674", "isbn");

print_r($book4->getItemData());
?>