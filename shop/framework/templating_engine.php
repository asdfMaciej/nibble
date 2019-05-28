<?php
interface TemplateInterface {
	public function generate($data);
}

class TemplateBuilder {
	protected $templates = [];
	protected $templates_data = [];

	protected $response_code = 200;

	public function __construct() {}

	public function addTemplate($filename, $data=[]) {
		if (is_string($filename)) {
			$tmp = new Template();
			if (!$tmp->setTemplateFile($filename)) {
				return False;
			}
		} elseif ($filename instanceof TemplateInterface) {
			$tmp = $filename;
		} else {
			throw new Exception("Template isn't neither a filename nor implements TemplateInterface.");
		}

		$this->templates[] = $tmp;
		$this->templates_data[] = $data;
	}

	public function setResponseCode($code) {
		$this->response_code = $code;
	}

	public function generate() {
		http_response_code($this->response_code);

		foreach ($this->templates as $n => $template) {
			$template->generate($this->templates_data[$n]);
		}
	}

}

class Template implements TemplateInterface {
	protected $template_dir = ROOT_PATH . "/templates/";
	protected $template_path = "";
	protected $nest_extract = [];

	public function __construct() {

	}

	public function setTemplateFile($filename) {
		$dir = $this->template_dir;
		$path = $dir . $filename;
		if (file_exists($path) && is_readable($path)) {
			$this->template_path = $path;
			return True;
		} else {
			throw new Exception("Cannot read template file: ".$filename.", path: ".$path);
		}
	}

	public function generate($data) {
		if ($this->template_path === "") {
			return;
		}
		$content = file_get_contents($this->template_path);
		$content = "?>" . $content;// . "<?php";
		$content = preg_replace('~{{ *([\w->\[\]"\']+) *}}~', '<?php echo(htmlspecialchars($$1, ENT_QUOTES, "UTF-8", false)); ?>', $content);

		extract($this->nest_extract);
		$this->nest_extract = $data;
		extract($this->nest_extract);

		eval($content);
	}

	protected function nest($filename, $data) { // input there should be correct
		$temp = new Template();
		$temp->setTemplateFile($filename);
		$temp->generate($data);
	}
}

?>