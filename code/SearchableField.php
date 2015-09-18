<?php

class SearchableField extends DataObject {
	private static $db = array(
		'Name' => 'Varchar', // the name of the field, e.g. Title
		'ClazzName' => 'Varchar', // the ClassName this field belongs to
		'Type' => 'Varchar' // the elasticsearch indexing type,
	);

	private static $has_one = array('SearchableClass' => 'SearchableClass');

	private static $display_fields = array('Name','HumanReadableInSiteTree');


	function getCMSFields() {
		$fields = new FieldList();

		$fields->push( new TabSet( "Root", $mainTab = new Tab( "Main" ) ) );
		$mainTab->setTitle( _t( 'SiteTree.TABMAIN', "Main" ) );
		$fields->addFieldToTab( 'Root.Main',  $nf = new TextField( 'Name', 'Name') );
		$nf->setDisabled(true);
		return $fields;

	}


	public function HumanReadableInSiteTree() {
		return $this->IsSearched ? 'Yes':'No';
	}
}