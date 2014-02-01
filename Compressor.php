<?php
/**
 * compresses the file using the selected library
 *  
 * @author Juan Pablo Leano
 *
 */
class Compressor {
	
	/**
	 * The compressor (library) to be used
	 */
	private $vendor;
	private $file;
	private $path;
	
	function __construct($file,$path,$type,$vendor = 'yuicompressor') {
		$this->file = $file;
		$this->path = $path;
		$this->vendor = $vendor;
		$this->type = $type;
	}
	
	public function process(){
		switch ($this->vendor) {
			case 'yuicompressor':
				exec('java -jar ' . APP . 'Vendor/yuicompressor/build/yuicompressor-2.4.7.jar --line-break 8000 --type ' . $this->type . " " . $this->path . $this->file , $output, $return);
				if($return != 0){
					throw new Exception("Yuicompressor could not compress the file");
				} 
				return implode("\n", $output);
				break;
			case 'csspp':
				if($this->type == "css"){
					App::import('Vendor', 'csspp' . DS . 'csspp');
					$filename = $this->path . $this->file;
					$data = file_get_contents($filename);
					$csspp = new csspp($filename,'');
					$output = $csspp->process();
					$ratio = 100 - (round(strlen($output) / strlen($data), 3) * 100);
					$output = " /* file: " . $this->file . ", ratio: $ratio% */ \n " . $output;
					return $output;
				} else {
					// csspp can only compress css files
					return file_get_contents($this->path . $this->file);
				}
				break;
			default:
				throw new Exception("Compressor not found");
			break;
		}
	}
}