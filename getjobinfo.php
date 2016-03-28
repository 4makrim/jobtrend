<?php
/*****************************************************************
 * Create a word cloud image for the job id passed as a 
 * POST parameter. 
 * joblist.php has the information of the job listings to be used. 
 * tagcloud.php uses the text in the selected job description to 
 * parses for keywords based on frequency and creates an image map.
 * The map can be used to provide more information about the keyword
 * name, frequency in text, etc. 
 *****************************************************************/
require dirname(__FILE__).'/tagcloud.php';
require dirname(__FILE__).'/joblist.php';

//Retrieve the posted job selection.
$q = $_POST['formJobs'];
$description = $joblist[$q]['description'];

// Uncomment to see the cleaned job description that is used for the wordcloud.
//echo "You selected Job with ID: $q.<br>$description<br>";
echo "<br><br><a href='view.php'><< Back to job selection</a><br><br>";

$font = dirname(__FILE__).'/Arial.ttf';
$width = 600;
$height = 600;
$cloud = new WordCloud($width, $height, $font, $description);
$palette = Palette::get_palette_from_hex($cloud->get_image(), array('FFA700', 'FFDF00', 'FF4F00', 'FFEE73'));
$cloud->render($palette);

// Render the cloud in a temporary file, and return its base64-encoded content
$file = tempnam(getcwd(), 'img');
imagepng($cloud->get_image(), $file);
$img64 = base64_encode(file_get_contents($file));
unlink($file);
imagedestroy($cloud->get_image());
?>
<img usemap="#mymap" src="data:image/png;base64,<?php echo $img64 ?>" border="0"/>
<map name="mymap">
<?php foreach($cloud->get_image_map() as $map): ?>
<area shape="rect" coords="<?php echo $map[1]->get_map_coords() ?>" onclick="alert('<?php echo $map[0] ?>');" />
<?php endforeach ?>
</map>

