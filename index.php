<?php
/***********************************************************************************
 * Author: Maya Mathews
 * TF-IDF Algorithm used explained below.
 * These weights are often combined into a tf-idf value, simply by multiplying them together. 
 * The best scoring words under tf-idf are uncommon ones which are repeated many times in the text, 
 * which lead early web search engines to be vulnerable to pages being stuffed with repeated terms 
 * to trick the search engines into ranking them highly for those keywords. For that reason, more 
 * complex weighting schemes are generally used, but tf-idf is still a good first step, especially 
 * for systems where no one is trying to game the system.
 * There are a lot of variations on the basic tf-idf idea, but a straightforward implementation 
 * might look like:

    <?php
    $tfidf = $term_frequency *  // tf
        log( $total_document_count / $documents_with_term, 2); // idf
    ?>

 * It's worth repeating that the IDF is the total document count over the count of the ones 
 * containing the term. So, if there were 50 documents in the collection, and two of them contained 
 * the term in question, the IDF would be 50/2 = 25. To be accurate, we should include the query in 
 * the IDF calculation, so if in the collection there are 50 documents, and 2 contain a term from 
 * the query, the actual calculation would be 50+1/2+1 = 51/3, as the query becomes part of the 
 * collection when we're searching.
 * 
	http://phpir.com/simple-search-the-vector-space-model
	http://phpir.com/text-classification
 *
 */

$joblist = array();
$content_collection = array();
$query = array();
/*
 * Uncomment this section after debugging with single page.
 * It takes a long time to navigate the entire list.
 * Future Enhancement: provide user page choice.
 */
$url = 'https://api-v2.themuse.com/jobs?page=';
$page = file_get_contents($url.'0');
$terms = json_decode($page);
$pagecount = $terms->page_count;
print "Retrieving the TF-IDF scores on the job postings<br>";
print "Search terms are created from a list of words used more than once in the postings.<br>";
print "Total number of pages: " . $terms->page_count . "<br>";
//for ($p = 0; $p < $pagecount; $p++){
//Limiting the number of jobs to 10 for testing.
print "Using listings in page 0-9: ";
for($p = 0; $p < 10; $p++){
	$page = file_get_contents($url.$p);
	getjobcodes($page);
}

/*
 * Test with a local text file with 20 records.
 */
//$page = file_get_contents('data1.json');

/*
 * Eliminate common words used in english language from the frequency table.
 * stopwords.txt was downloaded from http://www.textfixer.com/resources/common-english-words.txt
 */
$fh = file_get_contents('stopwords.txt');
$stopwords = explode(',',$fh);
	
/**
 * Parse the page and extract the job descriptions, id, publication date, and job title(name).
 * Each page has 20 records in json. Extending the array as additional pages are parsed.
 * @param unknown $page
 */
function getjobcodes($page){
	global $joblist;
	global $content_collection;
	$terms = json_decode($page);
	for ($i = 0; $i < sizeof($terms->results); $i++) {
		array_push($content_collection, strtolower(strip_tags($terms->results[$i]->contents)));
		array_push($joblist, array('publicationdate'=> substr($terms->results[$i]->publication_date,0,10), 'id' => $terms->results[$i]->id,
		'name' => $terms->results[$i]->name));
	}
}

/**
 * Create an index of searchable words to be used in relevance calculations.
 * df - document frequency. # of docs that the word was identified. 
 * tf - term frequency. # of times the word is found within a document. 
 * doc in this project refers to the job details or description for each posting.
 * 
 * @param array $content_collection
 * @return number[][]|number[][][]|NULL[][]
 */
function getIndex($content_collection) {
	global $stopwords;
	$dictionary = array ();
	$docCount = array ();
	//var_dump($content_collection);
	
		foreach ( $content_collection as $docID => $doc ) {
			$jobdocket = $docID;
			$terms = explode ( ' ', $doc );
			// get the number of tokens in the job description.
			$docCount [$jobdocket] = count ( $terms );
			foreach ( $terms as $term ) {
				// Use words that are not in the english stopwords list.
				if (! in_array ( $term, $stopwords ) && ctype_alpha ( $term )) {
					if (! isset ( $dictionary [$term] )) {
						$dictionary [$term] = array (
								'df' => 0,
								'postings' => array () 
						);
					}
					if (! isset ( $dictionary [$term] ['postings'] [$jobdocket] )) {
						$dictionary [$term] ['df'] ++;
						$dictionary [$term] ['postings'] [$jobdocket] = array (
								'tf' => 0 
						);
					}
					
					$dictionary [$term] ['postings'] [$jobdocket] ['tf'] ++;
				}
			}
	}
	/*
	 * Each page has 20 job postings. Uncomment to check if you have 
	 * the correct number of positngs.
	 */
	//var_dump($docCount);
	
	return array (
			'docCount' => $docCount,
			'dictionary' => $dictionary 
	);
} /* function getIndex(); */
	
/**
 * Displaying the tf-idf scores for the trending words in a table without formatting.
 */
echo '<div><table border="1"><tr><td>JobID</td><td>Date</td><td>Job Title</td><td>Score</td></tr>';	

$index = getIndex($content_collection);

/**
 * A trend is identified in multiple occurances of a pattern over the population.
 * It is also a pattern that can be identified over a period of time. 
 * In this respect, we are creating a query string with the words that are more 
 * frequently used words in the job postings. 
 * Atleast twice to establish a trend.
 */
foreach($index['dictionary'] as $term => $fr){
	$numdocs = $fr['df'];
	if ($numdocs > 2) {
		array_push($query, $term);
		/*
		 * To get a list of terms used more than twice. 
		 * //echo $term . ", ";
		 */
		
		/*
		 * To get an idea of the term frequency within each job description. 
		 */
		//foreach($fr['postings'] as $docid => $tf) {
			//echo " docID : " . $docid. " term frequency : " . $tf['tf'] . "<br>";
		//}
	}
}

$matchDocs = array();
$docCount = count($index['docCount']);

// Uncomment to limit query words to a smaller list. 
// We get a subset of listings that match the keywords.
//$query = ['SAP', 'warehouse'];

foreach($query as $qterm) {
	$lterm = strtolower($qterm);
	try {
		//if looking for a set of terms that are not in the keywords from userinput
		//ensure the word exists in atleast one of the documents. If not, continue to next word.
		if(!isset($index['dictionary'][$lterm])) continue;
		$entry = $index['dictionary'][$lterm];
		foreach($entry['postings'] as $docID => $posting) {
			$score = $posting['tf'] *
			log($docCount + 1 / $entry['df'] + 1, 2);
			if (!isset($matchDocs[$docID]))
				$matchDocs[$docID] = $score;
				else
					$matchDocs[$docID] +=  $score;
						
		}
	} catch (Exception $e) {
		echo "Query string not found in job :" . $docID, $e->getMessage(), "\n";
	}
}

// length normalise
foreach($matchDocs as $docID => $score) {
	$matchDocs[$docID] = $score/$index['docCount'][$docID];
}

arsort($matchDocs); // high to low
foreach($matchDocs as $docID => $score) {
	global $joblist;
	echo '<tr><td>'.$joblist[$docID]['id'].'</td><td>'.
		$joblist[$docID]['publicationdate'].'</td><td>'.
		$joblist[$docID]['name'].'</td><td>'.$matchDocs[$docID].'</td></tr>';
}
echo '</table></div>';

?>