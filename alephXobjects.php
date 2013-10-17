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
		$this->oclcNum = $this->getOCLCnum();
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

	} // end getAlephNum
	
	// Returns an array of all ISBNs found in the MARC record	
	public function getISBNsAll() {	
		$isbnFields = $this->marc->xpath("/present/record/metadata/oai_marc/varfield[@id='020']/subfield[@label='a']");
		$isbns = array();
		foreach ($isbnFields as $field) {
			$isbn = (string)$field[0];
			array_push($isbns, $isbn);
		} // end foreach			
	return($isbns);
	} // end getISBNsAll
	
	// Returns a single ISBN, favoring an ISBN-13 if found	
	public function getISBNjustOne() {	
		$isbnFields = $this->marc->xpath("/present/record/metadata/oai_marc/varfield[@id='020']/subfield[@label='a']");
		$isbns = array();
		foreach ($isbnFields as $field) {
			$isbn = (string)$field[0];
			array_push($isbns, $isbn);
		} // end foreach
		if (count($isbns) > 1) {
			$isbnPattern = "/^97[0-9]{11}.*$/";
			foreach ($isbns as $isbn) {
				if (preg_match($isbnPattern, $isbn)) {
					$goodISBN = $isbn;
				} // end if
			} // end foreach
		} // end if
		if ($goodISBN) {
			return($goodISBN);
		} else {
			return($isbns[0]);
		} // end if	
	} // end getISBNjustOne
	
	// Retrieves Aleph item data (call number, locations, etc)
	public function getItemData() {
		$itemData = $this->itemData;
		return($itemData);
	} // end getItemData	
	
	public function getAlephURL() {
		$docNumber = $this->alephNum;	
		$hostname = $this->hostname;
		$alephURL = $hostname."/docno=".$docNumber;
		return($alephURL);	
	} // end getAlephURL
	
	public function getOCLCnum() {
		$oclcPattern = "/^oc[A-z][0-9]{6,9}$/"; //regular expression pattern for matching oclc numbers (other data can be stored in 035 MARC fields)		
		//$author = $alephMarcXML->xpath("/present/record/metadata/oai_marc/varfield[@id='100']/subfield[@label='a']");
		foreach ($this->marc->record->metadata->oai_marc->varfield as $varfield) { //go through each MARC variable field in the result
    		if ($varfield->attributes()->id == "035") { //Find the 035 fields. see http://www.electrictoolbox.com/php-simplexml-element-attributes/ for accessing element attributes
        		if (preg_match($oclcPattern, $varfield->subfield)) { //look for 035 fields that store OCLC numbers
            		$oclcNumber = preg_replace("/^oc[A-z]/", "", $varfield->subfield); //remove the "ocn" or "ocm" prefix and store the oclc number as a variable                                
        		} // end if       
    		} // end if		
		} // end foreach
		return($oclcNumber);	
	} // end getOCLCnum
	
	public function getWorldcatLink() {
		$oclcnumber = $this->oclcNum;			
		$permalink = "http://umaryland.worldcat.org/oclc/".$oclcnumber; //build a permalink pointing to WCL out of the OCLC number
		return($permalink);
	}
	
	
} // end AlephX object

$book1 = new AlephX("31430045584994", "barcode");
$book2 = new AlephX("004320251", "aleph");
$book3 = new AlephX("MCD28", "callnum");

// oclcnums for testing. one is 7 digits. I think one of the others requires "ocn" prefix, others require "ocm" prefix
$oclcNums = array ("2648489", "173136007", "428436794", "34919814");

$book4 = new AlephX($oclcNums[3], "oclc");
$book5 = new AlephX("9780596100674", "isbn");
$book6 = new AlephX("0226103897", "isbn");

$books = array($book1, $book2, $book3, $book4, $book5, $book6);

/*
foreach ($books as $book) {
	echo $book->getAlephURL();
	echo "</br>";
}
*/


print_r($book6->getISBNsAll());
echo "</br>";

print_r($book5->getISBNsAll());
echo "</br>";

print_r($book4->getISBNsAll());
echo "</br>";
echo "</br>";

echo $book6->getISBNjustOne();
echo "</br>";
echo $book5->getISBNjustOne();
echo "</br>";
echo $book4->getISBNjustOne();
echo "</br>";
echo $book4->getWorldcatLink();
echo "</br>";

?>