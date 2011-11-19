<?php

//stop page load timing out on big recaches
set_time_limit(0);

$doc_root = $_SERVER['DOCUMENT_ROOT'];
$cache_root = "$doc_root/app/cache";

include "$doc_root/app/core/loader.php";

$valid_types = array("pages", "posts", "drafts", "collections", "snippets", "files");

//if no types set, recache all
$types = isset($_GET["type"]) ? array_map("trim", explode(",", $_GET["type"])) : $valid_types;

foreach($types as $type) {
	if(in_array($type, $valid_types)) {
		if(is_dir("$cache_root/$type"))
			rrmdir("$cache_root/$type");
	}
}

//remove "files" from list as can't cache in bulk
$key = array_search("files", $types);
if($key !== false)
	unset($types[$key]);

foreach($types as $type) {
	if(in_array($type, $valid_types)) {
		//create new ones
		$files = $Lando->get_content($type);

		//make sure content includes are cached (only parsed when called)
		foreach($files as $file) {
			if(method_exists($file, "content"))
				$file->content();
		}
	}
}

echo 'Latest content fetched for '.implode(", ", $types).".";