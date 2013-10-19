aleph-x-functions
=================

Functions for using the Aleph ILS X-services

## Create an Aleph X Object

Five ways:

```php
$book = new AlephX("31430045584994", "barcode");
$book = new AlephX("004320251", "aleph");
$book = new AlephX("MCD28", "callnum");
$book = new AlephX("173136007", "oclc");
$book = new AlephX("9780596100674", "isbn");
```

"barcode," "aleph," "callnum," etc. must be exact

## Available Methods (so far)

```php
// retrieve MARCxml as a PHP simpleXML object
$book->getMarc()

// retrieve item data (call number, holding locations, etc.) as a PHP simpleXML object
$book->getItemData()

// retrieve an Aleph system number as text
$book->getAlephNum()

// retrieve a stable URL pointing to the item in the Aleph OPAC
$book->getAlephURL() 

// retrieve a permalink to the item in Worldcat UMD
$book->getWorldcatLink()

// retrieve an OCLC number as text
$book->getOCLCnum()

// retrieve an array of all ISBNs found in the MARC record
$book->getISBNsAll();

// retrieve a single ISBN found in the MARC record, favoring an ISBN-13 if available
$book->getISBNjustOne()

// retrieve a barcode
$book->getBarcode();

```

### These are useful for testing

```
// retrieve a RESTful URL based your new AlephX parameters, for sending to Aleph X-services. Returns findXML
$book->getFindURL()

// the result of getFindURL(). Includes set number, number of results, etc.
getFindXML() 

// retrieve a RESTful URL based on set number from getFindXML(), for sending to Aleph X-services
$book->getPresentURL()

// retrieve a RESTful URL for retrieving item data
$book->getPresentURL()
```

