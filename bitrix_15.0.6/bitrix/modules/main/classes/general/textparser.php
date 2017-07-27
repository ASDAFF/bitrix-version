<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

class CTextParser
{
	public $type = "html";
	public $serverName = "";
	public $preg;

	public $imageWidth = 800;
	public $imageHeight = 800;
	public $maxStringLen = 0;
	public $maxAnchorLength = 40;
	public $arFontSize = array(
		1 => 40, //"xx-small"
		2 => 60, //"x-small"
		3 => 80, //"small"
		4 => 100, //"medium"
		5 => 120, //"large"
		6 => 140, //"x-large"
		7 => 160, //"xx-large"
	);
	public $allow = array(
		"HTML" => "N",
		"ANCHOR" => "Y",
		"BIU" => "Y",
		"IMG" => "Y",
		"QUOTE" => "Y",
		"CODE" => "Y",
		"FONT" => "Y",
		"LIST" => "Y",
		"SMILES" => "Y",
		"NL2BR" => "N",
		"VIDEO" => "Y",
		"TABLE" => "Y",
		"CUT_ANCHOR" => "N",
		"SHORT_ANCHOR" => "N",
		"ALIGN" => "Y",
		"USERFIELDS" => "N"
	);
	public $smiles = null;
	protected $wordSeparator = "\\s.,;:!?\\#\\-\\*\\|\\[\\]\\(\\)\\{\\}";
	protected $smilePatterns = null;
	protected $smileReplaces = null;
	protected static $defSmiles = null;
	protected $defended_urls = array();
	protected $anchorSchemes = null;
	protected $userField;
	public $bMobile = false;

	/* @deprecated */ public $allowImgExt = "gif|jpg|jpeg|png";

	function CTextParser()
	{
		$this->pathToSmile = '';
		$this->parser_nofollow = "N";
		$this->link_target = "_blank";
		$this->authorName = '';
	}

	public function getAnchorSchemes()
	{
		if($this->anchorSchemes === null)
		{
			static $schemes = null;
			if($schemes === null)
			{
				$schemes = \Bitrix\Main\Config\Option::get("main", "~parser_anchor_schemes", "http|https|news|ftp|aim|mailto|file");
			}
			$this->anchorSchemes = $schemes;
		}
		return $this->anchorSchemes;
	}

	public function setAnchorSchemes($schemes)
	{
		$this->anchorSchemes = $schemes;
	}

	protected function initSmiles()
	{
		if(static::$defSmiles === null)
		{
			$smiles = CSmile::getByType();

			$arSmiles = array();
			foreach($smiles as $smile)
			{
				$arTypings = explode(" ", $smile["TYPING"]);
				foreach ($arTypings as $typing)
				{
					$arSmiles[] = array_merge($smile, array(
						'TYPING' => $typing,
						'IMAGE'  => CSmile::PATH_TO_SMILE.$smile["SET_ID"]."/".$smile["IMAGE"],
						'DESCRIPTION' => $smile["NAME"],
						'DESCRIPTION_DECODE' => 'Y',
					));
				}
			}
			static::$defSmiles = $arSmiles;
		}
		$this->smiles = static::$defSmiles;
	}

	protected function initSmilePatterns()
	{
		$this->smilePatterns = array();
		$this->smileReplaces = array();

		$pre = "[^\\w&]";
		foreach ($this->smiles as $row)
		{
			if(preg_match("/\\w\$/", $row["TYPING"]))
				$pre .= "|".preg_quote($row["TYPING"], "/");
		}

		foreach ($this->smiles as $row)
		{
			if($row["TYPING"] <> '' && $row["IMAGE"] <> '')
			{
				$code = str_replace(array("'", "<", ">"), array("\\'", "&lt;", "&gt;"), $row["TYPING"]);
				$patt = preg_quote($code, "/");
				$code = preg_quote(str_replace(array("\x5C"), array("&#092;"), $code));
				$image = preg_quote(str_replace("'", "\\'", $row["IMAGE"]));
				$description = preg_quote(htmlspecialcharsbx(str_replace(array("\x5C"), array("&#092;"), $row["DESCRIPTION"]), ENT_QUOTES), "/");
				$width = intval($row["IMAGE_WIDTH"]);
				$height = intval($row["IMAGE_HEIGHT"]);
				$descriptionDecode = $row["DESCRIPTION_DECODE"] == 'Y'? true: false;

				if(in_array($row["TYPING"], array(":/", "8)")))
					$this->smilePatterns[] = "/(?<=".$pre.")$patt(?=.\\W|\\W.|\\W$)/ei".BX_UTF_PCRE_MODIFIER;
				else
					$this->smilePatterns[] = "/".$patt."/ei".BX_UTF_PCRE_MODIFIER;

				$this->smileReplaces[] = "\$this->convert_emoticon('".$code."', '".$image."', '".$description."', '".$width."', '".$height."', '".$descriptionDecode."')";
			}
		}
	}
	protected static function chr($a)
	{
		return \CharsetConverter::ConvertCharset($a, 'cp1251', SITE_CHARSET);
	}
	protected static function strpos($s, $a)
	{
		$a = self::chr($a);
		if (function_exists('mb_orig_strpos'))
			return mb_orig_strpos($s, $a);
		return strpos($s, $a);
	}
	function convertText($text)
	{
		$text = preg_replace(array("#([?&;])PHPSESSID=([0-9a-zA-Z]{32})#is", "/\\x{00A0}/".BX_UTF_PCRE_MODIFIER), array("\\1PHPSESSID1=", " "), $text);

		$this->type = ($this->type == "rss" ? "rss" : "html");
		$this->serverName = "";
		if($this->type == "rss")
		{
			$dbSite = CSite::GetByID(SITE_ID);
			$arSite = $dbSite->Fetch();
			$serverName = $arSite["SERVER_NAME"];
			if (strlen($serverName) <=0)
			{
				if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME)>0)
					$serverName = SITE_SERVER_NAME;
				else
					$serverName = COption::GetOptionString("main", "server_name", "www.bitrixsoft.com");
			}
			$this->serverName = "http://".$serverName;
		}

		$this->preg = array("counter" => 0, "pattern" => array(), "replace" => array(), "cache" => array());

		foreach(GetModuleEvents("main", "TextParserBefore", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->allow["HTML"] != "Y" && $this->allow['NL2BR'] == 'Y')
		{
			$text = preg_replace("#<br(.*?)>#is", "\n", $text);
		}

		if ($this->allow["CODE"]=="Y")
		{
			$text = preg_replace_callback(
				array(
					"#(\\[code(?:\\s+[^\\]]*\\]|\\]))(.+?)(\\[/code(?:\\s+[^\\]]*\\]|\\]))#is".BX_UTF_PCRE_MODIFIER,
					"#(<code(?:\\s+[^>]*>|>))(.+?)(</code(?:\\s+[^>]*>|>))#is".BX_UTF_PCRE_MODIFIER),
				array($this, "convertCode"),
				$text
			);
		}

		if ($this->allow["HTML"] != "Y")
		{
			if ($this->allow["ANCHOR"]=="Y")
			{
				$text = preg_replace(
					array(
						"#<a[^>]+href\\s*=\\s*('|\")(.+?)(?:\\1)[^>]*>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER,
						"#<a[^>]+href(\\s*=\\s*)([^'\">]+)>(.*?)</a[^>]*>#is".BX_UTF_PCRE_MODIFIER),
					"[url=\\2]\\3[/url]", $text
				);
			}
			if ($this->allow["BIU"]=="Y")
			{
				$replaced = 0;
				do
				{
					$text = preg_replace(
						"/<([busi])[^>a-z]*>(.+?)<\\/(\\1)[^>a-z]*>/is".BX_UTF_PCRE_MODIFIER,
						"[\\1]\\2[/\\1]",
					$text, -1, $replaced);
				}
				while($replaced > 0);
			}
			if ($this->allow["IMG"]=="Y")
			{
				$text = preg_replace(
					"#<img[^>]+src\\s*=[\\s'\"]*(((http|https|ftp)://[.-_:a-z0-9@]+)*(\\/[-_/=:.a-z0-9@{}&?%]+)+)[\\s'\"]*[^>]*>#is".BX_UTF_PCRE_MODIFIER,
					"[img]\\1[/img]", $text
				);
			}
			if ($this->allow["FONT"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\\<font[^>]+size\\s*=[\\s'\"]*([0-9]+)[\\s'\"]*[^>]*\\>(.+?)\\<\\/font[^>]*\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<font[^>]+color\\s*=[\\s'\"]*(\\#[a-f0-9]{6})[^>]*\\>(.+?)\\<\\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<font[^>]+face\\s*=[\\s'\"]*([a-z\\s\\-]+)[\\s'\"]*[^>]*>(.+?)\\<\\/font[^>]*>/is".BX_UTF_PCRE_MODIFIER
					),
					array(
						"[size=\\1]\\2[/size]",
						"[color=\\1]\\2[/color]",
						"[font=\\1]\\2[/font]"
					),
					$text
				);
			}
			if ($this->allow["LIST"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\\<ul((\\s[^>]*)|(\\s*))\\>(.+?)<\\/ul([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<ol((\\s[^>]*)|(\\s*))\\>(.+?)<\\/ol([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<li((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
					),
					array(
						"[list]\\4[/list]",
						"[list=1]\\4[/list]",
						"[*]",
					),
					$text
				);
			}
			if ($this->allow["TABLE"]=="Y")
			{
				$text = preg_replace(
					array(
						"/\\<table((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/table([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<tr((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/tr([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<td((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/td([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<th((\\s[^>]*)|(\\s*))\\>/is".BX_UTF_PCRE_MODIFIER,
						"/\\<\\/th([^>]*)\\>/is".BX_UTF_PCRE_MODIFIER,
					),
					array(
						"[table]",
						"[/table]",
						"[tr]",
						"[/tr]",
						"[td]",
						"[/td]",
						"[th]",
						"[/th]",
					),
					$text
				);
			}
			if ($this->allow["QUOTE"]=="Y")
			{
				$text = preg_replace("#<(/?)quote(.*?)>#is", "[\\1quote]", $text);
			}
			if (strlen($text)>0)
			{
				if ($this->preg["counter"] > 0)
				{
					$res = strlen((string)$this->preg["counter"]);
					$p = array('\d');
					while (($res--) > 1) $p[] = '\d{'.($res + 1).'}';
					$text = preg_replace(
						array("/\<(?!\017\#(".implode(")|(", $p).")\>)/", "/(?<!\<\017\#(".implode(")|(", $p)."))\>/", "/\"/"),
						array("&lt;", "&gt;", "&quot;"),
						$text
					);
				}
				else
				{
					$text = str_replace(
						array("<", ">", "/\"/"),
						array("&lt;", "&gt;", "&quot;"),
						$text
					);

				}
			}
		}
		$patt = array();
		if ($this->allow["VIDEO"] == "Y")
			$patt[] = "/\\[video([^\\]]*)\\](.+?)\\[\\/video[\\s]*\\]/is".BX_UTF_PCRE_MODIFIER;
		if ($this->allow["IMG"] == "Y")
			$patt[] = "/\\[img([^\\]]*)\\](.+?)\\[\\/img\\]/is".BX_UTF_PCRE_MODIFIER;
		if ($this->allow["ANCHOR"]=="Y")
		{
			$patt[] = "/\\[url\\](.*?)\\[\\/url\\]/i".BX_UTF_PCRE_MODIFIER;
			$patt[] = "/\\[url\\s*=\\s*(
			(?:
				[^\\[\\]]++
				|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\]
			)+
			)\\s*\\](.*?)\\[\\/url\\]/ixs".BX_UTF_PCRE_MODIFIER;

			$text = preg_replace_callback($patt, array($this, "preconvertAnchor"), $text);

			$word_separator = str_replace("?", "", $this->wordSeparator);
			$patt = array("/(?<=^|[".$word_separator."]|\\s)(?<!\\[nomodify\\]|<nomodify>)((".$this->getAnchorSchemes()."):\\/\\/[._:a-z0-9@-].*?)(?=[\\s'\"{}\\[\\]]|&quot;|\$)/is".BX_UTF_PCRE_MODIFIER);
			if (self::strpos($text, "�") !== false)
				$patt[] = "/(?<=[".self::chr("�")."])(?<!\\[nomodify\\]|<nomodify>)((".$this->getAnchorSchemes()."):\\/\\/[._:a-z0-9@-].*?)(?=[".self::chr("�")."])/is".BX_UTF_PCRE_MODIFIER;
			if (self::strpos($text, "�") !== false)
				$patt[] = "/(?<=[".self::chr("�")."])(?<!\\[nomodify\\]|<nomodify>)((".$this->getAnchorSchemes()."):\\/\\/[._:a-z0-9@-].*?)(?=[".self::chr("�")."])/is".BX_UTF_PCRE_MODIFIER;
			if (self::strpos($text, "�") !== false)
				$patt[] = "/(?<=[".self::chr("�")."])(?<!\\[nomodify\\]|<nomodify>)((".$this->getAnchorSchemes()."):\\/\\/[._:a-z0-9@-].*?)(?=[".self::chr("�")."])/is".BX_UTF_PCRE_MODIFIER;

			$text = preg_replace_callback($patt, array($this, "preconvertUrl"), $text);
		}
		else if (!empty($patt))
		{
			$text = preg_replace_callback($patt, array($this, "preconvertAnchor"), $text);
		}

		$text = preg_replace("/<\\/?nomodify>/i".BX_UTF_PCRE_MODIFIER, "", $text);

		foreach(GetModuleEvents("main", "TextParserBeforeTags", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->allow["SMILES"]=="Y")
		{
			if (strpos($text, "<nosmile>") !== false)
			{
				$text = preg_replace_callback(
					"/<nosmile>(.*?)<\\/nosmile>/is".BX_UTF_PCRE_MODIFIER,
					array($this, "defendTags"),
					$text
				);
			}
			if($this->smiles === null)
			{
				$this->initSmiles();
			}
			if(!empty($this->smiles))
			{
				if($this->smilePatterns === null)
				{
					$this->initSmilePatterns();
				}

				if (!empty($this->smilePatterns))
				{
					$text = preg_replace($this->smilePatterns, $this->smileReplaces, ' '.$text.' ');
				}
			}
		}

		$text = $this->post_convert_anchor_tag($text);

		$res = array_merge(
			array(
				"VIDEO" => "N",
				"IMG" => "N",
				"ANCHOR" => "N",
				"BIU" => "N",
				"LIST" => "N",
				"FONT" => "N",
				"TABLE" => "N",
				"ALIGN" => "N",
				"QUOTE" => "N"
			), $this->allow
		);
		foreach ($res as $tag => $val)
		{
			if ($val != "Y")
				continue;

			if (strpos($text, "<nomodify>") !== false)
			{
				$text = preg_replace_callback(
					"/<nomodify>(.*?)<\\/nomodify>/is".BX_UTF_PCRE_MODIFIER,
					array($this, "defendTags"),
					$text
				);
			}

			switch ($tag)
			{
				case "VIDEO":
					$text = preg_replace_callback(
						"/\\[video([^\\]]*)\\](.+?)\\[\\/video[\\s]*\\]/is".BX_UTF_PCRE_MODIFIER,
						array($this, "convertVideo"),
						$text
					);
					break;
				case "IMG":
					$text = preg_replace_callback(
						"/\\[img([^\\]]*)\\](.+?)\\[\\/img\\]/is".BX_UTF_PCRE_MODIFIER,
						array($this, "convertImage"),
						$text
					);
					break;
				case "ANCHOR":
					$arUrlPatterns = array(
						"/\\[url\\](.*?)\\[\\/url\\]/i".BX_UTF_PCRE_MODIFIER,
						"/\\[url\\s*=\\s*(
							(?:
								[^\\[\\]]++
								|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\]
							)+
							)\\s*\\](.*?)\\[\\/url\\]/ixs".BX_UTF_PCRE_MODIFIER,
					);

					if($this->allow["CUT_ANCHOR"] != "Y")
					{
						$text = preg_replace_callback(
							$arUrlPatterns,
							array($this, "convertAnchor"),
							$text
						);
					}
					else
					{
						$text = preg_replace($arUrlPatterns, "", $text);
					}
					break;

				case "BIU":
					$replaced  = 0;
					do
					{
						$text = preg_replace(
							"/\\[([busi])\\](.*?)\\[\\/(\\1)\\]/is".BX_UTF_PCRE_MODIFIER,
							"<\\1>\\2</\\1>",
						$text, -1, $replaced);
					}
					while($replaced > 0);
					break;
				case "LIST":
					while (preg_match("/\\[list\\s*=\\s*(1|a)\\s*\\](.+?)\\[\\/list\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
							array(
								"/\\[list\\s*=\\s*1\\s*\\](\\s*)(.+?)\\[\\/list\\](([\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[list\\s*=\\s*a\\s*\\](\\s*)(.+?)\\[\\/list\\](([\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[\\*\\]/".BX_UTF_PCRE_MODIFIER,
							),
							array(
								"<ol>\\2</ol>",
								"<ol type=\"a\">\\2</ol>",
								"<li>",
							),
							$text
						);
					}
					while (preg_match("/\\[list\\](.+?)\\[\\/list\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace(
							array(
								"/\\[list\\](\\s*)(.+?)\\[\\/list\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[\\*\\]/".BX_UTF_PCRE_MODIFIER,
								),
							array(
								"<ul>\\2</ul>",
								"<li>",
								),
							$text
						);
					}
					break;
				case "FONT":
					while (preg_match("/\\[size\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/size\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace_callback(
							"/\\[size\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/size\\]/is".BX_UTF_PCRE_MODIFIER,
							array($this, "convertFontSize"),
							$text
						);
					}
					while (preg_match("/\\[font\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/font\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace_callback(
							"/\\[font\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/font\\]/is".BX_UTF_PCRE_MODIFIER,
							array($this, "convertFont"),
							$text
						);
					}
					while (preg_match("/\\[color\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/color\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace_callback(
							"/\\[color\\s*=\\s*([^\\]]+)\\](.*?)\\[\\/color\\]/is".BX_UTF_PCRE_MODIFIER,
							array($this, "convertFontColor"),
							$text
						);
					}
					break;
				case "TABLE":
					while (preg_match("/\\[table\\](.+?)\\[\\/table\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace_callback(
							"/\\[table\\](.*?)\\[\\/table\\](?:(?:[\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
							array($this, "convertTable"),
							$text
						);
					}
					break;
				case "ALIGN":
					$replaced  = 0;
					do
					{
						$text = preg_replace(
							array(
								"/\\[left\\](.*?)\\[\\/left\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[right\\](.*?)\\[\\/right\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[center\\](.*?)\\[\\/center\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
								"/\\[justify\\](.*?)\\[\\/justify\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
							),
							array(
								"<div align=\"left\">\\1</div>",
								"<div align=\"right\">\\1</div>",
								"<div align=\"center\">\\1</div>",
								"<div align=\"justify\">\\1</div>",
							),
							$text, -1, $replaced);
					}
					while($replaced > 0);
					break;
				case "QUOTE":
					while (preg_match("/\\[quote[^\\]]*\\](.*?)\\[\\/quote[^\\]]*\\]/is".BX_UTF_PCRE_MODIFIER, $text))
					{
						$text = preg_replace_callback(
							"/\\[quote[^\\]]*\\](.*?)\\[\\/quote[^\\]]*\\](([\\040\\r\\t]*)\\n?)/is".BX_UTF_PCRE_MODIFIER,
							array($this, "convertQuote"),
							$text
						);
					}
					break;
			}
		}

		if (strpos($text, "<nomodify>") !== false)
		{
			$text = preg_replace_callback(
				"/<nomodify>(.*?)<\\/nomodify>/is".BX_UTF_PCRE_MODIFIER,
				array($this, "defendTags"),
				$text
			);
		}
		if (!empty($this->allow["USERFIELDS"]) && is_array($this->allow["USERFIELDS"]))
		{
			foreach($this->allow["USERFIELDS"] as $userField)
			{
				if (is_array($userField["USER_TYPE"]) && array_key_exists("TAG", $userField["USER_TYPE"]) )
				{
					$userField["TAG"] = $userField["USER_TYPE"]["TAG"];
				}
				if (empty($userField["TAG"]))
				{
					switch($userField["USER_TYPE_ID"])
					{
						case "webdav_element" :
							$userField["TAG"] = "DOCUMENT ID";
							break;
						case "vote" :
							$userField["TAG"] = "VOTE ID";
							break;
					}
				}

				if (!empty($userField["TAG"]) && array_key_exists("VALUE", $userField) && !empty($userField["VALUE"]) &&
					method_exists($userField["USER_TYPE"]["CLASS_NAME"], "GetPublicViewHTML") )
				{
					$userField["VALUE"] = (is_array($userField["VALUE"]) ? $userField["VALUE"] : array($userField["VALUE"]));
					$this->userField = $userField;
					$text = preg_replace_callback(
						"/\\[(".(is_array($userField["TAG"]) ? implode("|", $userField["TAG"]) : $userField["TAG"]).")\\s*=\\s*([a-z0-9]+)([^\\]]*)\\]/is".BX_UTF_PCRE_MODIFIER,
						array($this, "convert_userfields"),
						$text
					);
				}
			}
		}

		foreach(GetModuleEvents("main", "TextParserAfterTags", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->allow["HTML"] != "Y" || $this->allow['NL2BR'] == 'Y')
		{
			$text = str_replace("\n", "<br />", $text);

			$text = preg_replace(array(
				"/\\<br \\/\\>(\\<\\/table[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<thead[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/thead[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<tfoot[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/tfoot[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<tbody[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/tbody[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<tr[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/tr[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<td[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
				"/\\<br \\/\\>(\\<\\/td[^>]*\\>)/is".BX_UTF_PCRE_MODIFIER,
			),
			"\\1", $text);
		}

		$text = str_replace(
			array(
				"(c)", "(C)",
				"(tm)", "(TM)", "(Tm)", "(tM)",
				"(r)", "(R)"),
			array(
				"&#169;", "&#169;",
				"&#153;", "&#153;", "&#153;", "&#153;",
				"&#174;", "&#174;"),
			$text);

		if ($this->allow["HTML"] != "Y" && $this->maxStringLen > 0)
		{
			$text = preg_replace("/(\\&\\#\\d{1,3}\\;)/is".BX_UTF_PCRE_MODIFIER, "<\019\\1>", $text);
			$text = preg_replace_callback("/(?<=^|\\>)([^\\<\\[]+)(?=\\<|\\[|$)/is".BX_UTF_PCRE_MODIFIER, array($this, "partWords"), $text);
			$text = preg_replace("/(\\<\019((\\&\\#\\d{1,3}\\;))\\>)/is".BX_UTF_PCRE_MODIFIER, "\\2", $text);
		}

		foreach(GetModuleEvents("main", "TextParserBeforePattern", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		if ($this->preg["counter"] > 0)
			$text = str_replace($this->preg["pattern"], $this->preg["replace"], $text);

		foreach(GetModuleEvents("main", "TextParserAfter", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$text, &$this));

		return trim($text);
	}

	public function defendTags($matches)
	{
		return $this->defended_tags($matches[1], 'replace');
	}

	function defended_tags($text, $tag = 'replace')
	{
		$text = str_replace("\\\"", "\"", $text);
		switch ($tag)
		{
			case "replace":
				$this->preg["pattern"][] = "<\017#".$this->preg["counter"].">";
				$this->preg["replace"][] = $text;
				$text = "<\017#".$this->preg["counter"].">";
				$this->preg["counter"]++;
				break;
		}
		return $text;
	}

	function convert4mail($text)
	{
		$text = Trim($text);
		if (strlen($text)<=0) return "";

		$arPattern = array();
		$arReplace = array();

		$arPattern[] = "/\\[(code|quote)(.*?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\\[\\/(code|quote)(.*?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\\<WBR[\\s\\/]?\\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/^(\r|\n)+?(.*)$/";
		$arReplace[] = "\\2";

		$arPattern[] = "/\\[b\\](.+?)\\[\\/b\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\\[i\\](.+?)\\[\\/i\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\\[u\\](.+?)\\[\\/u\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\\[s\\](.+?)\\[\\/s\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\\[(\\/?)(color|font|size|left|right|center)([^\\]]*)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\\[url\\](\\S+?)\\[\\/url\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(URL: \\1 )";

		$arPattern[] = "/\\[url\\s*=\\s*(\\S+?)\\s*\\](.*?)\\[\\/url\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (URL: \\1 )";

		$arPattern[] = "/\\[img\\](.+?)\\[\\/img\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\1)";

		$arPattern[] = "/\\[video([^\\]]*)\\](.+?)\\[\\/video[\\s]*\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\\[(\\/?)list\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";

		$arPattern[] = "/\\[user([^\\]]*)\\](.+?)\\[\\/user\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2";

		$arPattern[] = "/\\[DOCUMENT([^\\]]*)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\\[DISK(.+?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\\[(table)(.*?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\\[\\/table(.*?)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\\[tr\\]\\s+/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/\\[(\\/?)(tr|td)\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$text = preg_replace($arPattern, $arReplace, $text);

		$text = str_replace("&shy;", "", $text);
		$text = str_replace("&nbsp;", " ", $text);
		$text = str_replace("&quot;", "\"", $text);
		$text = str_replace("&#092;", "\\", $text);
		$text = str_replace("&#036;", "\$", $text);
		$text = str_replace("&#33;", "!", $text);
		$text = str_replace("&#91;", "[", $text);
		$text = str_replace("&#93;", "]", $text);
		$text = str_replace("&#39;", "'", $text);
		$text = str_replace("&lt;", "<", $text);
		$text = str_replace("&gt;", ">", $text);
		$text = str_replace("&nbsp;", " ", $text);
		$text = str_replace("&#124;", '|', $text);
		$text = str_replace("&amp;", "&", $text);

		return $text;
	}

	public function convertVideo($matches)
	{
		return $this->convert_video($matches[1], $matches[2]);
	}

	private function convert_video($params, $path)
	{
		if (strlen($path) <= 0)
			return "";

		AddEventHandler("main", "TextParserVideoConvert", array("CTextParser", "TextParserConvertVideo"), 1000);

		$width = "";
		$height = "";
		$preview = "";
		$provider = "";
		preg_match("/width\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);

		preg_match("/preview\\='([^']+)'/is".BX_UTF_PCRE_MODIFIER, $params, $preview);
		if (empty($preview))
			preg_match("/preview\\=\"([^\"]+)\"/is".BX_UTF_PCRE_MODIFIER, $params, $preview);

		preg_match("/type\\=(YOUTUBE|RUTUBE|VIMEO)/is".BX_UTF_PCRE_MODIFIER, $params, $provider);

		$width = intval($width[1]);
		$width = ($width > 0 ? $width : 400);
		$height = intval($height[1]);
		$height = ($height > 0 ? $height : 300);
		$preview = trim($preview[1]);
		$preview = (strlen($preview) > 0 ? $preview : "");
		$provider = isset($provider[1]) ? strtoupper(trim($provider[1])) : '';

		$arFields = array(
			"PATH" => $path,
			"WIDTH" => $width,
			"HEIGHT" => $height,
			"PREVIEW" => $preview,
			"TYPE" => $provider,
			"PARSER_OBJECT" => $this
		);

		$video = '';
		foreach(GetModuleEvents("main", "TextParserVideoConvert", true) as $arEvent)
		{
			$video = ExecuteModuleEventEx($arEvent, array(&$arFields));
		}

		return $this->defended_tags($video, 'replace');
	}

	function convert_emoticon($code = "", $image = "", $description = "", $width = "", $height = "", $descriptionDecode = false)
	{
		if ($code == '' || $image == '')
			return '';
		$code = stripslashes($code);
		$description = stripslashes($description);
		$image = stripslashes($image);
		$width = intval($width);
		$height = intval($height);
		if ($descriptionDecode)
			$description = htmlspecialcharsback($description);

		$html = '<img src="'.$this->serverName.$this->pathToSmile.$image.'" border="0" data-code="'.$code.'" alt="'.$code.'" '.($width > 0? 'width="'.$width.'"':'').' '.($height > 0? 'height="'.$height.'"':'').' title="'.$description.'" class="bx-smile" />';
		$cacheKey = md5($html);
		if (!isset($this->preg["cache"][$cacheKey]))
			$this->preg["cache"][$cacheKey] = $this->defended_tags($html, 'replace');

		return $this->preg["cache"][$cacheKey];
	}

	public function convertCode($matches)
	{
		$text = $matches[2];

		if (strlen($text)<=0)
			return '';

		$text = str_replace(
			array("[nomodify]", "[/nomodify]", "&#91;", "&#93;", "&", "<", ">", "\\r", "\\n", "\\\"", "\\", "[", "]", "  ", "\t"),
			array("", "", "[", "]", "&#38;", "&#60;", "&#62;", "&#92;r", "&#92;n", "\"", "&#92;", "&#91;", "&#93;", "&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;"),
			$text
		);

		$text = stripslashes($text);

		return $this->defended_tags($this->convert_open_tag('code')."<pre>".$text."</pre>".$this->convert_close_tag('code'), 'replace');
	}

	public function convertQuote($matches)
	{
		return $this->convert_quote_tag($matches[1]);
	}

	public function convertTable($matches)
	{
		$text = preg_replace(
			array(
				"/(\\s*?)\\[tr\\](.*?)\\[\\/tr\\](\\s*?)/is".BX_UTF_PCRE_MODIFIER,
				"/(\\s*?)\\[td\\](.*?)\\[\\/td\\](\\s*?)/is".BX_UTF_PCRE_MODIFIER,
				"/(\\s*?)\\[th\\](.*?)\\[\\/th\\](\\s*?)/is".BX_UTF_PCRE_MODIFIER,
				),
			array(
				"<tr>\\2</tr>",
				"<td>\\2</td>",
				"<th>\\2</th>",
				),
			$matches[1]
		);
		return "<table class=\"data-table\">".$text."</table>";
	}

	function convert_quote_tag($text = "")
	{
		if (strlen($text)<=0)
			return '';

		$text = str_replace("\\\"", "\"", $text);

		return $this->convert_open_tag('quote').$text.$this->convert_close_tag('quote');
	}

	function convert_open_tag($marker = "quote")
	{
		$marker = (strtolower($marker) == "code" ? "code" : "quote");

		$this->{$marker."_open"}++;
		if ($this->type == "rss")
			return "\n====".$marker."====\n";
		return "<div class='".$marker."'><table class='".$marker."'><tr><td>";
	}

	function convert_close_tag($marker = "quote")
	{
		$marker = (strtolower($marker) == "code" ? "code" : "quote");

		if ($this->{$marker."_open"} == 0)
		{
			$this->{$marker."_error"}++;
			return '';
		}
		$this->{$marker."_closed"}++;

		if ($this->type == "rss")
			return "\n=============\n";
		return "</td></tr></table></div>";
	}

	public function convertImage($matches)
	{
		return $this->convert_image_tag($matches[2], $matches[1]);
	}

	function convert_image_tag($url = "", $params = "")
	{
		$url = trim($url);
		if (strlen($url)<=0)
			return '';

		preg_match("/width\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $width);
		preg_match("/height\\=([0-9]+)/is".BX_UTF_PCRE_MODIFIER, $params, $height);
		$width = intval($width[1]);
		$height = intval($height[1]);

		$bErrorIMG = false;
		if (!$bErrorIMG && !preg_match("/^(http|https|ftp|\\/)/i".BX_UTF_PCRE_MODIFIER, $url))
			$bErrorIMG = true;

		$url = htmlspecialcharsbx($url);
		if ($bErrorIMG)
			return "[img]".$url."[/img]";

		$strPar = "";
		if($width > 0)
		{
			if($width > $this->imageWidth)
			{
				$height = intval($height * ($this->imageWidth / $width));
				$width = $this->imageWidth;
			}
		}
		if($height > 0)
		{
			if($height > $this->imageHeight)
			{
				$width = intval($width * ($this->imageHeight / $height));
				$height = $this->imageHeight;
			}
		}
		if($width > 0)
			$strPar = " width=\"".$width."\"";
		if($height > 0)
			$strPar .= " height=\"".$height."\"";

		if(strlen($this->serverName) <= 0 || preg_match("/^(http|https|ftp)\\:\\/\\//i".BX_UTF_PCRE_MODIFIER, $url))
			return '<img src="'.$url.'" border="0"'.$strPar.' data-bx-image="'.$url.'" />';
		else
			return '<img src="'.$this->serverName.$url.'" border="0"'.$strPar.' data-bx-image="'.$this->serverName.$url.'" />';
	}

	public function convertFont($matches)
	{
		return $this->convert_font_attr('font', $matches[1], $matches[2]);
	}

	public function convertFontSize($matches)
	{
		return $this->convert_font_attr('size', $matches[1], $matches[2]);
	}

	public function convertFontColor($matches)
	{
		return $this->convert_font_attr('color', $matches[1], $matches[2]);
	}

	function convert_font_attr($attr, $value = "", $text = "")
	{
		if (strlen($text)<=0)
			return "";

		$text = str_replace("\\\"", "\"", $text);

		if (strlen($value) <= 0)
			return $text;

		if ($attr == "size")
		{
			if (strlen($value) > 2 && substr($value, -2) == 'pt')
			{
				$value = intVal(substr($value, 0, -2));
				if ($value <= 0)
					return $text;
				return '<span style="font-size:'.$value.'pt; line-height: normal;">'.$text.'</span>';
			}

			$count = count($this->arFontSize);
			if ($count <= 0)
				return $text;
			$value = intval($value > $count ? ($count - 1) : $value);
			return '<span style="font-size:'.$this->arFontSize[$value].'%;">'.$text.'</span>';
		}
		elseif ($attr == 'color')
		{
			$value = preg_replace("/[^\\w#]/", "" , $value);
			return '<span style="color:'.$value.'">'.$text.'</span>';
		}
		elseif ($attr == 'font')
		{
			$value = preg_replace("/[^\\w\\s\\-\\,]/", "" , $value);
			return '<span style="font-family:'.$value.'">'.$text.'</span>';
		}
		return '';
	}

	function convert_userfields($matches)
	{
		static $vars = null;
		if ($vars === null)
		{
			$vars = get_object_vars($this);
		}

		$vars["bMobile"] = $this->bMobile;

		$userField = $this->userField;
		$id = $matches[2];
		if ($userField["USER_TYPE"]["USER_TYPE_ID"] == "disk_file" || in_array($id, $userField["VALUE"]))
		{
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->RegisterTag("webdav_element_internal_".$id);
			}

			return call_user_func_array(
				array($userField["USER_TYPE"]["CLASS_NAME"], "GetPublicViewHTML"),
				array($userField, $id, $matches[3], $vars, $matches)
			);
		}
		return $matches[0];
	}

	// Only for public using
	function wrap_long_words($text="")
	{
		if ($this->maxStringLen > 0 && !empty($text))
		{
			$text = str_replace(array(chr(11), chr(12), chr(34), chr(39)), array("", "", chr(11), chr(12)), $text);
			$text = preg_replace_callback("/(?<=^|\\>)([^\\<]+)(?=\\<|$)/is".BX_UTF_PCRE_MODIFIER, array($this, "partWords"), $text);
			$text = str_replace(array(chr(11), chr(12)), array(chr(34), chr(39)), $text);
		}
		return $text;
	}

	public function partWords($matches)
	{
		return $this->part_long_words($matches[1]);
	}

	function part_long_words($str)
	{
		$word_separator = $this->wordSeparator;
		if (($this->maxStringLen > 0) && (strlen(trim($str)) > 0))
		{
			$str = str_replace(
				array(chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8),
					"&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;",
					chr(34), chr(39)),
				array("", "", "", "", "", "", "", "",
					chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6),
					chr(7), chr(8)),
				$str
			);
			$str = preg_replace_callback(
				"/(?<=[".$word_separator."]|^)(([^".$word_separator."]+))(?=[".$word_separator."]|$)/is".BX_UTF_PCRE_MODIFIER,
				array($this, "cutWords"),
				$str
			);
			$str = str_replace(
				array(chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), "&lt;WBR/&gt;", "&lt;WBR&gt;", "&amp;shy;"),
				array("&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", chr(34), chr(39), "<WBR/>", "<WBR/>", "&shy;"),
				$str
			);
		}
		return $str;
	}

	public function cutWords($matches)
	{
		return $this->cut_long_words($matches[2]);
	}

	function cut_long_words($str)
	{
		if (($this->maxStringLen > 0) && (strlen($str) > 0))
			$str = preg_replace("/([^ \n\r\t\x01]{".$this->maxStringLen."})/is".BX_UTF_PCRE_MODIFIER, "\\1<WBR/>&shy;", $str);
		return $str;
	}

	public function convertAnchor($matches)
	{
		return $this->convert_anchor_tag($matches[1], ($matches[2] <> ''? $matches[2] : $matches[1]), '');
	}

	function convert_anchor_tag($url, $text, $pref="")
	{
		$url = trim(str_replace(array("[nomodify]", "[/nomodify]"), "", $url));
		$text = trim(str_replace(array("[nomodify]", "[/nomodify]"), "", $text));
		$text = (strlen($text) <= 0 ? $url : $text);

		$bTextUrl = ($text == $url);
		$bShortUrl = ($this->allow["SHORT_ANCHOR"] == "Y");

		$text = str_replace("\\\"", "\"", $text);
		$end = "";
		$pattern = "/([\\.,\\?\\!\\;]|&#33;)$/".BX_UTF_PCRE_MODIFIER;
		if ($bTextUrl && preg_match($pattern, $url, $match))
		{
			$end = $match[1];
			$url = preg_replace($pattern, "", $url);
			$text = preg_replace($pattern, "", $text);
		}

		if (preg_match("/\\[\\/(quote|code|img|imag|video)/i", $url))
			return $url;

		$url = preg_replace(
			array(
				"/&amp;/".BX_UTF_PCRE_MODIFIER,
				"/javascript:/i".BX_UTF_PCRE_MODIFIER,
				"/[".chr(12)."\\']/".BX_UTF_PCRE_MODIFIER,
				"/&#91;/".BX_UTF_PCRE_MODIFIER,
				"/&#93;/".BX_UTF_PCRE_MODIFIER
			),
			array(
				"&",
				"java script&#58; ",
				"%27",
				"[",
				"]"
			),
			$url
		);

		if (substr($url, 0, 1) != "/" && !preg_match("/^(".$this->getAnchorSchemes().")\\:/i".BX_UTF_PCRE_MODIFIER, $url))
			$url = "http://".$url;
		$text = preg_replace(
			array("/&amp;/i".BX_UTF_PCRE_MODIFIER, "/javascript:/i".BX_UTF_PCRE_MODIFIER),
			array("&", "javascript&#58; "),
			$text
		);

		if ($bShortUrl &&
			strlen($text) > $this->maxAnchorLength &&
			preg_match("/^(".$this->getAnchorSchemes()."):\\/\\/(\\S+)$/i".BX_UTF_PCRE_MODIFIER, $text, $matches))
		{
			$uri_type = $matches[1];
			$stripped = $matches[2];
			$text = $uri_type.'://'.(strlen($stripped) > $this->maxAnchorLength ?
					substr($stripped, 0, $this->maxAnchorLength-10).'...'.substr($stripped, -10) :
					$stripped
				);
		}

		$url = htmlspecialcharsbx(htmlspecialcharsback($url));

		return $pref.($this->parser_nofollow == "Y" ? '<noindex>' : '').'<a href="'.$url.'" target="'.$this->link_target.'"'.($this->parser_nofollow == "Y" ? ' rel="nofollow"' : '').'>'.$text.'</a>'.($this->parser_nofollow == "Y" ? '</noindex>' : '').$end;
	}

	private function preconvertUrl($matches)
	{
		return $this->pre_convert_anchor_tag($matches[0], $matches[0], "[url]".$matches[0]."[/url]");
	}

	public function preconvertAnchor($matches)
	{
		return $this->pre_convert_anchor_tag($matches[1], $matches[2], $matches[0]);
	}

	function pre_convert_anchor_tag($url, $text = "", $str = "")
	{
		$c = count($this->defended_urls);
		$tag = "<\x18#".$c.">";
		if (stripos($str, "[url") !== 0)
		{
			$this->defended_urls[$tag] = $str;
		}
		else if(strlen($text) > 0)
		{
			$word_separator = str_replace(array("\\]", "\\[", "?"), "", $this->wordSeparator);
			$text = preg_replace(
				"/(?<=^|[".$word_separator."]|\\s)(?<!\\[nomodify\\]|<nomodify>)((".$this->getAnchorSchemes()."):\\/\\/[._:a-z0-9@-].*?)(?=[\\s'\"{}\\[\\]]|&quot;|\$)/is".BX_UTF_PCRE_MODIFIER,
				"\\1", $text
			);
			$this->defended_urls[$tag] = "[url=".$url."]".$text."[/url]";
		}
		else
		{
			$this->defended_urls[$tag] = "[url]".$url."[/url]";
		}
		return $tag;
	}

	function post_convert_anchor_tag($str)
	{
		if (!empty($this->defended_urls))
			return str_replace(array_reverse(array_keys($this->defended_urls)), array_reverse(array_values($this->defended_urls)), $str);
		else
			return $str;
	}

	function TextParserConvertVideo($arParams)
	{

		global $APPLICATION;

		if(
			empty($arParams) 
			|| strlen($arParams["PATH"]) <= 0
		)
		{
			return false;
		}
		
		if (
			isset($arParams["PARSER_OBJECT"])
			&& is_object($arParams["PARSER_OBJECT"])
		)
		{
			$ob = $arParams["PARSER_OBJECT"];
		}

		ob_start();

		if ($arParams["TYPE"] == 'YOUTUBE' || $arParams["TYPE"] == 'RUTUBE' || $arParams["TYPE"] == 'VIMEO')
		{
			if (
				(!defined("BX_MOBILE_LOG") || BX_MOBILE_LOG !== true)
				&& (!$ob || !$ob->bMobile)
			)
			{
				// Replace http://someurl, https://someurl by //someurl
				$arParams["PATH"] = preg_replace("/https?:\/\//is", '//', $arParams["PATH"]);
				echo '<iframe src="'.$arParams["PATH"].'" allowfullscreen="" frameborder="0" height="'.$arParams["HEIGHT"].'" width="'.$arParams["WIDTH"].'"></iframe>';
			}
			else
			{
				?><a href="<?=$arParams["PATH"]?>"><?=$arParams["PATH"]?></a><?
			}
		}
		else
		{
			if (
				(defined("BX_MOBILE_LOG") && BX_MOBILE_LOG === true)
				|| ($ob && $ob->bMobile)
			)
			{
				?><div onclick="return BX.eventCancelBubble(event);"><?
			}

			$APPLICATION->IncludeComponent(
				"bitrix:player", "",
				array(
					"PLAYER_TYPE" => "auto",
					"USE_PLAYLIST" => "N",
					"PATH" => $arParams["PATH"],
					"WIDTH" => $arParams["WIDTH"],
					"HEIGHT" => $arParams["HEIGHT"],
					"PREVIEW" => $arParams["PREVIEW"],
					"LOGO" => "",
					"FULLSCREEN" => "Y",
					"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
					"SKIN" => "bitrix.swf",
					"CONTROLBAR" => "bottom",
					"WMODE" => "transparent",
					"HIDE_MENU" => "N",
					"SHOW_CONTROLS" => "Y",
					"SHOW_STOP" => "N",
					"SHOW_DIGITS" => "Y",
					"CONTROLS_BGCOLOR" => "FFFFFF",
					"CONTROLS_COLOR" => "000000",
					"CONTROLS_OVER_COLOR" => "000000",
					"SCREEN_COLOR" => "000000",
					"AUTOSTART" => "N",
					"REPEAT" => "N",
					"VOLUME" => "90",
					"DISPLAY_CLICK" => "play",
					"MUTE" => "N",
					"HIGH_QUALITY" => "Y",
					"ADVANCED_MODE_SETTINGS" => "N",
					"BUFFER_LENGTH" => "10",
					"DOWNLOAD_LINK" => "",
					"DOWNLOAD_LINK_TARGET" => "_self"
				),
				null,
				array(
					"HIDE_ICONS" => "Y"
				)
			);

			if (
				(defined("BX_MOBILE_LOG") && BX_MOBILE_LOG === true)
				|| ($ob && $ob->bMobile)
			)
			{
				?></div><?
			}
		}

		$video = ob_get_contents();
		ob_end_clean();
		return $video;
	}

	function strip_words($string, $count)
	{
		$splice_pos = null;

		$ar = preg_split("/(<.*?>|\\s+)/s", $string, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($ar as $i => $s)
		{
			if(substr($s, 0, 1) != "<")
			{
				$count -= strlen($s);
				if($count <= 0)
				{
					$splice_pos = $i;
					break;
				}
			}
		}

		if(isset($splice_pos))
		{
			array_splice($ar, $splice_pos+1);
			return implode('', $ar);
		}
		else
		{
			return $string;
		}
	}

	function closeTags($html)
	{
		preg_match_all("#<([a-z0-9]+)([^>]*)(?<!/)>#i".BX_UTF_PCRE_MODIFIER, $html, $result);
		$openedtags = $result[1];

		preg_match_all("#</([a-z0-9]+)>#i".BX_UTF_PCRE_MODIFIER, $html, $result);
		$closedtags = $result[1];
		$len_opened = count($openedtags);

		if(count($closedtags) == $len_opened)
			return $html;

		$openedtags = array_reverse($openedtags);

		for($i = 0; $i < $len_opened; $i++)
		{
			if (!in_array($openedtags[$i], $closedtags))
				$html .= '</'.$openedtags[$i].'>';
			else
				unset($closedtags[array_search($openedtags[$i], $closedtags)]);
		}

		return $html;
	}

	public static function clearAllTags($text)
	{
		$text = strip_tags(Trim($text));
		if (strlen($text)<=0) return "";

		if (stripos($text, "<cut") !== false || stripos($text, "[cut") !== false)
		{
			$text = preg_replace(array(
				"/^(.+?)<cut(.*?)>/is".BX_UTF_PCRE_MODIFIER,
				"/^(.+?)\[cut(.*?)\]/is".BX_UTF_PCRE_MODIFIER
			), "\\1", $text);
		}
		if (stripos($text, "[quote") !== false)
		{
			while (preg_match("/\\[(?:quote)(?:.*?)\\](.*?)\\[\\/quote(.*?)\\]/is".BX_UTF_PCRE_MODIFIER, $text))
			{
				$text = preg_replace(
					array(
						"/\\[quote(?:.*?)\\](.*?)\\[\\/quote(.*?)\\]/is".BX_UTF_PCRE_MODIFIER,
						"/<quote(?:.*?)>(.*?)<\\/quote(.*?)>/is".BX_UTF_PCRE_MODIFIER
					),
					"\"\\1\"",
					$text
				);
			}
		}

		$arPattern = array();
		$arReplace = array();

		$arPattern[] = "/\\<WBR[\\s\\/]?\\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/^(\r|\n)+?(.*)$/";
		$arReplace[] = "\\2";

		$arPattern[] = "/\\[url\\s*=\\s*(\\S+?)\\s*\\](.*?)\\[\\/url\\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\2 (\\1)";

		$arPattern[] = "/\<(\/?)(code|font|color|video)(.*?)\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";
		$arPattern[] = "/\[(\/?)(b|i|u|s|list|code|quote|size|font|color|url|img|video|td|tr|table|file|document id|disk file id|user|left|right|center|justify|\\*)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		return preg_replace($arPattern, $arReplace, $text);
	}

	function html_cut($html, $size)
	{
		$symbols = strip_tags($html);
		$symbols_len = strlen($symbols);

		if($symbols_len < strlen($html))
		{
			$strip_text = $this->strip_words($html, $size);

			if($symbols_len > $size)
				$strip_text = $strip_text."...";

			$final_text = $this->closetags($strip_text);
		}
		elseif($symbols_len > $size)
			$final_text = substr($html, 0, $size)."...";
		else
			$final_text = $html;

		return $final_text;
	}
}
