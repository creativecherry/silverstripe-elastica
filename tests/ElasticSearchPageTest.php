<?php

/**
 * @package comments
 */
class ElasticSearchPageTest extends ElasticsearchBaseTest {

	public static $fixture_file = 'elastica/tests/ElasticaTest.yml';


	public function testCMSFields() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		$fields = $searchPage->getCMSFields();

		$mainTab = $this->checkTabExists($fields,'Main');
		$this->checkFieldExists($mainTab, 'Identifier');

		$searchTab = $this->checkTabExists($fields,'SearchDetails');

		$this->checkFieldExists($searchTab, 'InfoField');
		$this->checkFieldExists($searchTab, 'SearchHelper');
		$this->checkFieldExists($searchTab, 'ElasticSearchPageSearchField');
		$this->checkFieldExists($searchTab, 'SearchFieldsMessage');
	}


	public function testInvalidClassName() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');
		$searchPage->ClassesToSearch = 'ThisClassDoesNotExist';

		try {
			$searchPage->write();
			$this->fail('Page should not be writeable');
		} catch (ValidationException $e) {
			$this->assertEquals('The class ThisClassDoesNotExist does not exist', $e->getMessage());
		}
	}



	public function testNonSearchableClass() {
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		// This does not implement searchable
		$searchPage->ClassesToSearch = 'Member';

		try {
			$searchPage->write();
			$this->fail('Page should not be writeable');
		} catch (ValidationException $e) {
			$this->assertEquals('The class Member must have the Searchable extension', $e->getMessage());
		}
	}


	/*
	Test setting up a search page for data objects as if editing the CMS directly
	 */
	public function testSearchPageForDataObjects() {
		echo "========================== TEST STARTS NOW ==========================\n";
		//$this->devBuild();
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		$searchPage->ClassesToSearch = 'FlickrPhotoTO';
		$searchPage->InSiteTree = false;
		$searchPage->Title = '**** Flickr Photo Search ****';
		$searchPage->write();
		//$searchPage->publish('Stage', 'Live');

		$filter = array('ClazzName' => 'FlickrPhoto', 'Name' => 'Title');

		//Check fieldnames as expected
		$searchableFields = $searchPage->ElasticaSearchableFields();
		$sfs = $searchableFields->map('Name')->toArray();
		sort($sfs);
		$expected = array('Aperture','AspectRatio','Description','FirstViewed','FlickrID',
			'FlickrSetTOs','FlickrTagTOs','FocalLength35mm','ISO','Photographer','ShutterSpeed',
			'TakenAt','Title');
		$this->assertEquals($expected, $sfs);
	}


	/*
	Test that during the build process, requireDefaultRecords creates records for
	each unique field name declared in searchable_fields
	 */
	public function testSearchableFieldsCreatedAtBuildTime() {

		$searchableTestPage = $this->objFromFixture('SearchableTestPage', 'first');
		$searchPage = $this->objFromFixture('ElasticSearchPage', 'search');

		// expected mapping of searchable classes to searchable fields that will be
		// stored in the database as SearchableClass and SearchableField
		$expected = array(
			'Page' => array('Title','Content'),
			'SiteTree' => array('Title','Content'),
			'SearchableTestPage' => array('Title','Content','Country','PageDate'),
			'FlickrTagTO' => array('RawValue'),
			'FlickrAuthorTO' => array('PathAlias','DisplayName','FlickrPhotoTOs'),
			'FlickrPhotoTO' => array('Title','FlickrID','Description','TakenAt', 'Aperture',
				'ShutterSpeed','FocalLength35mm','ISO','Photographer','FlickrTagTOs','FlickrSetTOs',
				'FirstViewed','AspectRatio'),
			'FlickrSetTO' => array('Title','FlickrID','Description')
		);





		// check the expected classes
		$expectedClasses = array_keys($expected);
		$nSearchableClasses = SearchableClass::get()->count();
		$this->assertEquals(sizeof($expectedClasses), $nSearchableClasses);


 		$searchPage->SiteTreeOnly = true;
		$searchPage->Content = 'some random string';
		$searchPage->write();
		$scs = SearchableClass::get();

		$sfs = $searchPage->SearchableFields();



		// check the names expected to appear

		$fieldCtr = 0;
		foreach ($expectedClasses as $expectedClass) {
			$sc = SearchableClass::get()->filter('Name', $expectedClass)->first();
			$this->assertEquals($expectedClass,$sc->Name);

			$inSiteTree = 1;
			$start = substr($expectedClass, 0,6);
			if ($start == 'Flickr') {
				$inSiteTree = 0;
			};
			echo $sc->Name.', ist='.$sc->InSiteTree.'\n';
			$this->assertEquals($inSiteTree,$sc->InSiteTree);

			$expectedNames = $expected[$expectedClass];
			foreach ($expectedNames as $expectedName) {
				$filter = array('Name' => $expectedName, 'SearchableClassID' => $sc->ID );
				$sf = SearchableField::get()->filter($filter)->first();
				$this->assertEquals($expectedName, $sf->Name);
				$fieldCtr++;
			}
		}
		$nSearchableFields = SearchableField::get()->count();

		foreach (SearchableField::get()->sort('Name') as $sf) {
			echo $sf->Name."\n";
		}

		$this->assertEquals($fieldCtr, $nSearchableFields);
	}
}






