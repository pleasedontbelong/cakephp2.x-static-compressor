<?php
/**
* JS / CSS minifier
*
* PHP versions 4 and 5
*
* CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
* Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
*
* @author Pablo Leano
*/
App::uses('File', 'Utility');
class Mini {
	
	const CSS = "css";
	const JS = "js";
	
	private $response;
	private $filename;
	private $path;
	private $type;
	
	public function __construct($url,CakeResponse $response,$isCss = false,$isJs = false) {
		$this->url = $url;
		$this->response = $response;
		//get the type of file (js or css)
		if($isCss){
			$this->type = Mini::CSS;
			$this->path = CSS;
		}
		if($isJs){
			$this->type = Mini::JS;
			$this->path = JS;
		}
		if(!$this->type){
			throw new Exception("Incorrect type of file");
		}
		
		//extract the filename from the url
		if (preg_match('|\.\.|', $url) || !preg_match('#^(ccss|cjs)/(.+)$#i', $url, $matches)) {
			throw new Exception('Wrong file name');
		}
		$this->filename = $matches[2];
	}
	
	public function process(){
		
		//checks if the file exist in the webroot/css , or if it's in cache (case of merged files)
		// if the file is found on cache, changes the $this->cachename
		$this->_validates();
		
		$cachefile = CACHE . $this->type . DS . str_replace(array('/','\\'), '-', $this->filename);
		
		//the the compressed file either from cache or from using a compressor
		if (file_exists($cachefile)) {
			$templateModified = filemtime($this->path . $this->filename);
			$cacheModified = filemtime($cachefile);
			
			//if the file is more recent than the cache, compress it
			if ($templateModified > $cacheModified) {
				$output = $this->_getCompressed();
				$this->_writeToCache($cachefile, $output);
			} else {
				$output = file_get_contents($cachefile);
			}
		} else {
			$output = $this->_getCompressed();
			$this->_writeToCache($cachefile, $output);
			$templateModified = time();
		}
		//create the response body and headers
		$this->response->header('Date',date("D, j M Y G:i:s ", $templateModified) . 'GMT');
		//set the correct content type
		//$this->response->header('Content-Type',($this->type == Mini::CSS ? "text/css" : "application/x-javascript"));
		$this->response->type(($this->type == Mini::CSS ? "text/css" : "application/x-javascript"));
		$this->response->header('Expires', gmdate("D, d M Y H:i:s", time() + DAY) . " GMT");
		$this->response->header('Cache-Control','max-age=86400, must-revalidate'); // HTTP/1.1
		$this->response->header('Pragma: cache');        // HTTP/1.0
		$this->response->body($output);
		$this->response->send();
	}
	
	/**
	 * Gets the compressed content of the current file
	 */
	private function _getCompressed(){
		App::import('Vendor', 'Compressor');
		// here i'm using csspp but you could also use Yuicompressor
		$compressor = new Compressor($this->filename, $this->path, $this->type);
		return $compressor->process();
	}
	
	/**
	 * Write the content to a file
	 * @param string $cachefile path for the cache file
	 * @param string $content file's content
	 * @throws Exception
	 */
	private function _writeToCache($cachefile, $content){
		$cache = new File($cachefile);
		if(!$cache->write($content)){
			throw new Exception('Could not write cache file');
		}	
	}
	
	private function _validates(){
		if (($this->type == Mini::CSS && !file_exists(CSS . $this->filename)) || ($this->type == Mini::JS && !file_exists(JS . $this->filename))) {
			//check file exists on cache
			if(!file_exists(CACHE . $this->type . DS . $this->filename)){
				throw new Exception('File not found');
			}
		}
	}
	
}

$mini = new Mini($url,$response,$isCss,$isJs);
try {
	$mini->process();
} catch (Exception $e) {
	exit($e->getMessage());
}