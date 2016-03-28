<?php
/**
 * Limiting the joblist to posts from 4 pages.
 * Ideally these images can be cached and do not need to be created on the fly.
 * 
 * @var unknown
 */
$joblist = array();
/*
 * Uncomment this section after debugging with single page.
 * It takes a long time to navigate the entire list.
 *
 */
$url = 'https://api-v2.themuse.com/jobs?page=';
$page = file_get_contents($url.'0');
$terms = json_decode($page);
$pagecount = $terms->page_count;
//print "Total number of pages: " . $terms->page_count . "<br>";

function addjobstolist($page){
	global $joblist;
	$terms = json_decode($page);
	for ($i = 0; $i < sizeof($terms->results); $i++) {
		$jobdescription = strtolower(strip_tags($terms->results[$i]->contents));
		array_push($joblist, array(
				'publicationdate'=> substr($terms->results[$i]->publication_date,0,10), 
				'id' => $terms->results[$i]->id,
				'name' => $terms->results[$i]->name,
				'description' => $jobdescription));
	}
}

//Limiting the number of jobs to 4 pages for testing.
//for($p = 0; $p < $pagecount; $p++){
for($p = 0; $p < 4; $p++){
	$page = file_get_contents($url.$p);
	addjobstolist($page);
}

?>
