<?php
use Sunra\PhpSimple\HtmlDomParser;

require_once AWECHAT_PLUGIN_DIR . 'vendor/autoload.php';

class Awechat_dom {
	var $html;
	var $dom;

	public function __construct($html) {
		$this->html = $html;
		$this->dom = HtmlDomParser::str_get_html( $html );
	}

	public function getHtml() {
		return $this->dom->innertext;
	}

	public function formatPost() {
		// $brs = $this->dom->find('br');
		// foreach ($brs as $br) $br->tag = 'p';
		$as = $this->dom->find('a');
		foreach ($as as $a) $a->innertext .= ': ' . $a->href;
		$this->addStyle('p', "font-size:15px; line-height:28px; color:#595959;font-family:'Helvetica Neue','Microsoft Yahei'; margin:15px 5px");
		$this->addStyle('pre, code', "font-size:14px; font-family: Roboto, 'Courier New', Consolas, Inconsolata, Courier, monospace;");
		$this->addStyle('code', "margin:0 3px; padding:0 6px; white-space: pre-wrap; background-color:#F8F8F8; border-radius:2px; display: inline;");
		$this->addStyle('pre', "font-size:15px; line-height:20px;");
		$this->addStyle('precode', "white-space: pre; overflow:auto; border-radius:3px; padding:5px10px; display: block !important;");
		$this->addStyle('strong, b', "color:#BF360C;");
		$this->addStyle('em, i', "color:#009688;");
		$this->addStyle('big', "font-size:22px; color:#009688; font-weight: bold; vertical-align: middle; border-bottom:1px solid #eee;");
		$this->addStyle('small', "font-size:12px; line-height:22px;");
		$this->addStyle('hr', "border-bottom:0.05em dotted #eee; margin:10px auto;");
		$this->addStyle('table, pre, dl, blockquote, q, ul, ol', "margin:10px 5px;");
		$this->addStyle('ul, ol', "padding-left:10px;");
		$this->addStyle('li', "margin:5px;");
		$this->addStyle('lip', "margin:5px 0!important;");
		$this->addStyle('ul ul, ul ol, ol ul, ol ol', "margin:0; padding-left:10px;");
		$this->addStyle('ol ol, ul ol', "list-style-type: lower-roman;");
		$this->addStyle('ul ul ol, ul ol ol, ol ul ol, ol ol ol', "list-style-type: lower-alpha;");
		$this->addStyle('dl', "padding:0;");
		$this->addStyle('dl dt', "font-size:1em; font-weight: bold; font-style: italic;");
		$this->addStyle('dl dd', "margin:0 0 10px; padding:0 10px;");
		$this->addStyle('blockquote, q', "border-left:3px solid #009688; padding:0 10px; color:#777; quotes: none;");
		$this->addStyle('blockquote p', "margin:5px 0;");
		$this->addStyle('h1', "margin:20px 0 10px; padding:0; font-weight: bold; color:#009688;");
		$this->addStyle('h2', "margin:20px 0 10px; padding:0; font-weight: bold; color:#009688;");
		$this->addStyle('h3', "margin:20px 0 10px; padding:0; font-weight: bold; color:#009688;");
		$this->addStyle('h4', "margin:20px 0 10px; padding:0; font-weight: bold; color:#009688;");
		$this->addStyle('h5', "margin:20px 0 10px; padding:0; font-weight: bold; color:#009688;");
		$this->addStyle('h6', "margin:20px 0 10px; padding:0; font-weight: bold; color:#009688;");
		$this->addStyle('h1', "font-size:24px; text-align: center; border-bottom:1px solid #ddd;");
		$this->addStyle('h2', "font-size:22px; text-align: center;");
		$this->addStyle('h3', "font-size:18px; border-bottom:1px solid #eee;");
		$this->addStyle('h4', "font-size:16px;");
		$this->addStyle('h5', "font-size:15px;");
		$this->addStyle('h6', "font-size:15px; color:#777;");
		$this->addStyle('table', "padding:0; border-collapse: collapse; border-spacing:0; font-size:1em; font: inherit; border:0;");
		$this->addStyle('tbody', "margin:0; padding:0; border:0;");
		$this->addStyle('table tr', "border:0; border-top:1px solid #CCC; background-color: white; margin:0; padding:0;");
		$this->addStyle('table tr:nth-child(2n)', "background-color:#F8F8F8;");
		$this->addStyle('table tr th', "font-size:16px; border:1px solid #CCC; margin:0; padding:5px10px;");
		$this->addStyle('table tr td', "font-size:16px; border:1px solid #CCC; margin:0; padding:5px10px;");
		$this->addStyle('table tr th', "font-weight: bold; background-color:#F0F0F0;");
		$this->addStyle('figcaption', "text-align: center; font-size: 14px; color: #aaa;");
	}
	private function addStyle($tagart, $style) {
		$doms = $this->dom->find($tagart);
		foreach ($doms as $dom) {
		  $style_attr = $dom->getAttribute('style');
		  if ($style_attr) $style_attr .= ';';
		  $style_attr .= $style;
		  $dom->setAttribute('style', $style_attr);
		}
	}

	public function getImagesSrc() {
		$imagesSrc = array();
		$imageDoms = $this->dom->find('img');
		foreach ($imageDoms as $imageDom) {
			$imgSrc = $imageDom->getAttribute('src');
			if ($imgSrc) array_push($imagesSrc, $imgSrc);
		}
		return $imagesSrc;
	}
}