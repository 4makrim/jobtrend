<?php 
require dirname(__FILE__).'/joblist.php';
?>
<!DOCTYPE html>
<html>
<head>
</head>
<body>
<h1>Select a Job Title and submit query to view the keywords in the posting.</h1>
<form action="getjobinfo.php" method="POST";>
	<select name='formJobs'>
	<?php 
		$i = 0;
		foreach ($joblist as $job){
			$jobtitle = $job['name'];
			echo "<option value=$i>$jobtitle</option>\n";	
			$i = $i + 1;
		}
	?>
	</select>
	<input type="submit" />
	</form>
<div id="jobdetails">
</div>
</body>
</html>
