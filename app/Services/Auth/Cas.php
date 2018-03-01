<?php

namespace App\Services\Auth;

use App\Models\AuthCas;
use Illuminate\Http\Request;

class Cas extends AuthService
{
	protected $name = 'cas';
	private $URL = 'https://cas.utc.fr/cas/';
	protected $processURL;		// Callback pour process login
	protected $config;

	public function __construct() {
		$this->processURL = route('login.process', ['provider' => $this->name]);
		$this->config = config("auth.services." . $this->name);
	}

	public function showLoginForm() {
		return redirect()->away($this->URL.'login?service='.$this->processURL);
	}

	public function login(Request $request) {
		$ticket = $request->query('ticket');							// Ticket CAS
		if (!isset($ticket) || empty($ticket))
			return redirect()->route('login.show')->withError('Ticket CAS invalide');	// TODO
		$data = file_get_contents($this->URL.'serviceValidate?service='.$this->processURL.'&ticket='.$ticket);
		if (empty($data))
			return redirect()->route('login.show')->withError('Ticket CAS invalide');	// TODO

		$parsed = new xmlToArrayParser($data);

		if (!isset($parsed->array['cas:serviceResponse']['cas:authenticationSuccess']))
			return redirect()->route('login', ['provider' => $this->name]); //->withError('Ticket CAS invalide');	// TODO

		$userArray = $parsed->array['cas:serviceResponse']['cas:authenticationSuccess'];

		// On cherche si l'utilisateur existe déjà dans la BDD
		$userAuth = $this->findUser($this->config['model'], 'login', $userArray['cas:user']);
		// TODO pass array

		$user_infos = [
			'firstname' => $userArray['cas:attributes']['cas:givenName'],
			'lastname' 	=> $userArray['cas:attributes']['cas:sn'],
			'email' 	=> $userArray['cas:attributes']['cas:mail'],
		];
		$auth_infos = [
			'login' => $userArray['cas:user'],
			'email' => $userArray['cas:attributes']['cas:mail'],
		];

		if ($userAuth === null)			// Si inconnu, on le crée et on le connecte.
			$this->create($this->config['model'], $user_infos, $auth_infos);
		else							// Si connu, on actualise ses infos et on le connecte.
			$this->update($this->config['model'], $userAuth->user_id, $user_infos, $auth_infos);

		return redirect($this->config['redirection']);
	}

	public function logout() {

	}
}





class xmlToArrayParser
{
	/** The array created by the parser can be assigned to any variable: $anyVarArr = $domObj->array.*/
	public  $array = array();
	public  $parse_error = false;
	private $parser;
	private $pointer;

	/** Constructor: $domObj = new xmlToArrayParser($xml); */
	public function __construct($xml) {
		$this->pointer =& $this->array;
		$this->parser = xml_parser_create("UTF-8");
		xml_set_object($this->parser, $this);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($this->parser, "tag_open", "tag_close");
		xml_set_character_data_handler($this->parser, "cdata");
		$this->parse_error = xml_parse($this->parser, ltrim($xml))? false : true;
	}

	/** Free the parser. */
	public function __destruct() { xml_parser_free($this->parser);}

	/** Get the xml error if an an error in the xml file occured during parsing. */
	public function get_xml_error() {
		if($this->parse_error) {
			$errCode = xml_get_error_code ($this->parser);
			$thisError =  "Error Code [". $errCode ."] \"<strong style='color:red;'>" . xml_error_string($errCode)."</strong>\",
			at char ".xml_get_current_column_number($this->parser) . "
			on line ".xml_get_current_line_number($this->parser)."";
		}else $thisError = $this->parse_error;
		return $thisError;
	}

	private function tag_open($parser, $tag, $attributes) {
		$this->convert_to_array($tag, 'attrib');
		$idx = $this->convert_to_array($tag, 'cdata');
		if (isset($idx)) {
			$this->pointer[$tag][$idx] = Array('@idx' => $idx,'@parent' => &$this->pointer);
			$this->pointer =& $this->pointer[$tag][$idx];
		} else {
			$this->pointer[$tag] = Array('@parent' => &$this->pointer);
			$this->pointer =& $this->pointer[$tag];
		}
		if (!empty($attributes)) { $this->pointer['attrib'] = $attributes; }
	}

	/** Adds the current elements content to the current pointer[cdata] array. */
	private function cdata($parser, $cdata) { $this->pointer['cdata'] = trim($cdata); }

	private function tag_close($parser, $tag) {
		$current = & $this->pointer;
		if(isset($this->pointer['@idx'])) {unset($current['@idx']);}

		$this->pointer = & $this->pointer['@parent'];
		unset($current['@parent']);

		if(isset($current['cdata']) && count($current) == 1) { $current = $current['cdata'];}
		else if(empty($current['cdata'])) {unset($current['cdata']);}
	}

	/** Converts a single element item into array(element[0]) if a second element of the same name is encountered. */
	private function convert_to_array($tag, $item) {
		if (isset($this->pointer[$tag][$item])) {
			$content = $this->pointer[$tag];
			$this->pointer[$tag] = array((0) => $content);
			$idx = 1;
		} else if (isset($this->pointer[$tag])) {
			$idx = count($this->pointer[$tag]);
			if (!isset($this->pointer[$tag][0])) {
				foreach ($this->pointer[$tag] as $key => $value) {
					unset($this->pointer[$tag][$key]);
					$this->pointer[$tag][0][$key] = $value;
				}
			}
		} else
			$idx = null;
		return $idx;
	}
}
