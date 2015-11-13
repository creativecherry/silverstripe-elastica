<?php
use SilverStripe\Elastica\ElasticSearcher;
use Elastica\Type;
class ElasticSearcherUnitTest extends ElasticsearchBaseTest {
	public static $fixture_file = 'elastica/tests/lotsOfPhotos.yml';

	public static $ignoreFixtureFileFor = array('testResultsForEmptySearch');

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}


	public function testSuggested() {
		$es = new ElasticSearcher();
		$locale = \i18n::default_locale();
		$es->setLocale($locale);
		$es->setClasses('FlickrPhotoTO');

		//FIXME when awake

		// This doesn't work, possibly a bug $fields = array('Description.standard' => 1,'Title.standard' => 1);
		$fields = array('Description' => 1,'Title' => 1);
		$results = $es->search('New Zealind', $fields);

		$this->assertEquals('New Zealand', $es->getSuggestedQuery());
	}

	public function testResultsForEmptySearch() {
		$es = new ElasticSearcher();

		$es->hideResultsForEmptySearch();
		$this->assertFalse($es->getShowResultsForEmptySearch());

		$es->showResultsForEmptySearch();
		$this->assertTrue($es->getShowResultsForEmptySearch());
	}


	public function testMoreLikeThisSinglePhoto() {
		$fp = $this->objFromFixture('FlickrPhotoTO', 'photo0076');

		echo "FP: Original title: {$fp->Title}\n";
		$es = new ElasticSearcher();
		$locale = \i18n::default_locale();
		$es->setLocale($locale);
		$es->setClasses('FlickrPhotoTO');

		$fields = array('Description.standard' => 1,'Title.standard' => 1);
		$results = $es->moreLikeThis($fp, $fields);

		echo "RESULTS:\n";
		foreach ($results as $result) {
			echo "-\t{$result->Title}\n";
		}

		$terms = $results->getList()->MoreLikeThisTerms;
		print_r($terms);

		$fieldNamesReturned = array_keys($terms);
		$fieldNames = array_keys($fields);
		sort($fieldNames);
		sort($fieldNamesReturned);

		echo "T1\n";
		print_r($fieldNames);
		echo "T2\n";
		print_r($fieldNamesReturned);

		$this->assertEquals($fieldNames, $fieldNamesReturned);

		//FIXME - this seems anomolyous, check in more detail
		$expected = array('texas');
		$this->assertEquals($expected, $terms['Title.standard']);

		$expected = array('new', 'see','photographs', 'information','resolution', 'view', 'high',
		'collection', 'company', 'orleans', 'pacific', 'degolyer', 'southern', 'file',
		'everett', 'railroad', 'texas');




		$this->assertEquals($expected, $terms['Description.standard']);
	}

}
