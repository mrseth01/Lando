<?php

class Text extends File {
	public $raw_content;
	public $manual_metadata = array();
	
	private function swap_vars($content) {
		$global_vars = array(
			"site_title",
			"site_description",
			"site_root",
			"theme_dir"
		);

		$regex = '\{\{\s*('.implode("|", $global_vars).')\s*}}';

		$parse 		= "(?<!\\\)$regex";
		$noparse 	= "\\\($regex)";

		$content = preg_replace("~$parse~e", '$GLOBALS["$1"]', $content);
		$content = preg_replace("~$noparse~", "$1", $content);

		return $content;
	}

	private function swap_funcs($content) {
		$regex = '\{\{\s*(\w+)(\s+(\w+:)?("[^"]*"|\w+|\d+|true|false))+\s*}}';

		$parse 		= "(?<!\\\)$regex";
		$noparse 	= "\\\($regex)";
	
		$content = preg_replace("~$parse~ie",
														'$this->process_include("$0", "$1")',
														$content);
		
		$content = preg_replace("~$noparse~i", "$1", $content);

		return $content;
	}

	private function swap_includes($content) {
		$content = $this->swap_vars($content);
		$content = $this->swap_funcs($content);
		return $content;
	}
	
	private function process_include($str, $func) {
		$args_str = str_ireplace($func, "", $str);
		$allowed_funcs = array("snippet", "gallery", "slideshow", "collection", "share", "url");
		
		global $Lando;
		if(isset($Lando->config["custom_include_functions"]))
			$allowed_funcs = array_merge($allowed_funcs, $Lando->config["custom_include_functions"]);
		
		if(!in_array($func, $allowed_funcs) || !function_exists($func))
			return $str;
		
		preg_match_all('~\s+(?:(\w+):)?("[^"]*"|\w+|\d+|true|false)~', $args_str, $matches, PREG_SET_ORDER);
		
		$args = array();
		
		foreach($matches as $match) {
			//if key undefined, default to title (allows simpler {{foo "Title"}} includes)
			if(!$match[1])
				$match[1] = "title";
			
			$args[$match[1]] = trim($match[2], '"');
		}

		//if just passing title in an array, pass as a string
		if(array_keys($args) === array("title"))
			$args = $args["title"];
	
		$include = $func($args);
		
		if(!$include || (is_object($include) && !method_exists($include, "__toString")))
			return $str;
			
		return compress_html((string)$include);
	}

	private function get_file_url($path, $thumb=false) {
		global $Lando;
		$path = trim_slashes($path);
		$File = $Lando->get_file($path, $thumb);
		
		if(!$File)
			return false;
		
		return $File->url();
	}
	
	private function resolve_media_srcs($content, $dir) {
		if(preg_match_all('/<(?<tag>img|audio|video|source)[^>]+src="(?<src>[^"]*)"[^>]*>/i', $content, $elements)) {
			foreach($elements["src"] as $i => $src) {
				$is_img = ($elements["tag"][$i] == "img");

				//if relative url
				if(strpos($src, ":") === false) {
					$resolved = resolve_path($src, $dir);

					$new_src = $this->get_file_url($resolved);
					
					if($new_src)
						$content = str_replace('"'.$src.'"', '"'.$new_src.'"', $content);
				}
			}
		}
		
		return $content;
  }
  
  //get functions
	public function metadata($key=null) {
		if(!$key)
			return $this->manual_metadata;

		$key = strtolower($key);
	
		if(!isset($this->manual_metadata[$key]))
			return false;
		
		return $this->manual_metadata[$key];
	}
  
  public function content() {
		global $Lando;
		
		//swap in include content
		$content = $this->swap_includes($this->raw_content);
		
		//parse to HTML using appropriate parser
		$content = $this->to_html($content);
		
		$content = $this->resolve_media_srcs($content, $this->path);
		
		if($Lando->config["smartypants"] && function_exists("SmartyPants"))
			$content = SmartyPants($content);
		
		return $content;
	}
	
	public function raw_content() {
		return $this->raw_content;
	}
}