<?
if (!$USER->CanDoOperation('fileman_edit_existent_files'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

class spellChecker
{
	function init($lang_default="en",$skip_len=2,$use_pspell,$use_custom_spell,$dic_path="",$mode=PSPELL_FAST,$personal_path="")
	{
		$this->lang = $lang_default;
		$this->skip_len = $skip_len;

		$this->pspell = (function_exists('pspell_config_create') && ($use_pspell=="Y"));
		$this->custom_spell = ($use_custom_spell=="Y");

		if ($this->pspell)
		{
			$this->mode = $mode;
			$this->personal_path = $personal_path;
			$this->pspellConfig();
		}
		elseif($this->custom_spell)
		{
			$this->dic_path = $dic_path;
			switch ($this->lang)
			{
				case 'ru':
					$this->letters = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','ь','э','ю','я');
					break;
				case 'en':
					$this->letters = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','v','z');
					break;
			}
			$this->customConfig();
		}
	}

	function pspellConfig()
	{
		$pspell_config = pspell_config_create ($this->lang);
		pspell_config_ignore($pspell_config, $this->skip_len);
		pspell_config_mode($pspell_config, $this->mode);
		pspell_config_personal($pspell_config, $this->personal_path);
		$this->pspell_link = pspell_new_config($pspell_config);
	}

	function customConfig()
	{
		$this->dic = array();

	}

	function codeLetter($letter){
		return (in_array($letter, $this->letters) && $letter != 'ы' && $letter != 'ь' && $letter != 'ъ') ? ord($letter) : 'def';
	}

	function loadDic($letter)
	{
		$path = $this->dic_path.'/dics/'.$this->lang.'_';
		$path .= $letter.'.dic';
		if (is_readable($path))
		{
			$dic = file($path);
			foreach ($dic as $dict_word)
				$this->dic[$letter][string_lower(trim($dict_word))] = $dict_word;
		}
		else
			$this->dic[$letter] = array();
	}


	function check($word)
	{
		//pspell
		if ($this->pspell)
		{
			if ($this->lang == 'ru')
				$word = convertCharsetInKOI8($word);
			return pspell_check($this->pspell_link, $word);
		//custom
		}
		elseif($this->custom_spell)
		{
			if ($this->lang == 'ru')
				$word = convertCharsetInWIN1251($word);

			if (strlen($word)<=$this->skip_len)
				return true;
			$first_let = $this->codeLetter(string_lower($word{0}));
			if (!isset($this->dic[$first_let]))
				$this->loadDic($first_let);
			//check if word exist in array
			if (isset($this->dic[$first_let][string_lower($word)]))
				return true;
			return false;
		}
   	}


	function checkArr($wordArr)
	{
		$this->wrongWordArr = array();
		for ($i = 0; $i < count($wordArr); $i++)
		{
			if (!$this->check($wordArr[$i]))
			{
				$resultElement = array(
					0 => $i,
					1 => $this->suggest($wordArr[$i])
				);
				$this->wrongWordArr[] = $resultElement;
			}

		}
		return $this->wrongWordArr;
	}


	function suggest($word)
	{
		$suggestions = array();
		//pspell
		if ($this->pspell)
		{
			if ($this->lang == 'ru')
				$word = convertCharsetInKOI8($word);
			$suggestions = pspell_suggest($this->pspell_link, $word);
			if ($this->lang == 'ru')
				$suggestions= array_map("convertCharsetFromKOI8", $suggestions);
		//custom
		}
		elseif($this->custom_spell)
		{
			if ($this->lang == 'ru')
				$word = convertCharsetInWIN1251($word);

			$first_let = $this->codeLetter(string_lower($word{0}));
			$wordLen = strlen($word);
			$n = $wordLen;
			$lcount = count($this->letters);

			for ($i=1;$i<=$wordLen;$i++)
			{
				//пропуск буквы
				$variant = substr($word,0,$i-1).substr($word,-($wordLen-$i),$wordLen-$i);
				if ($this->dic[$first_let][string_lower($variant)])
					$suggestions[] = $variant;

				//замена буквы
				for ($j=0;$j<$lcount;$j++)
				{
					$variant = substr($word,0,$i-1).$this->letters[$j].substr($word,-($wordLen-$i),$wordLen-$i);
					if ($this->dic[$first_let][string_lower($variant)])
						$suggestions[] = $variant;

				}
			}
			for ($i=1;$i<=$wordLen;$i++)
			{
				for ($j=0;$j<$lcount;$j++)
				{
					//вставка буквы
					$variant = substr($word,0,$i).$this->letters[$j].substr($word,$i);
					if ($this->dic[$first_let][string_lower($variant)])
						$suggestions[] = $variant;
				}
			}

			for ($i=0;$i<=$wordLen-2;$i++)
			{
				//замена букв местами
				$variant = substr($word,0,$i).substr($word,$i+1,1).substr($word,$i,1).substr($word,$i+2);
				if ($this->dic[$first_let][string_lower($variant)])
					$suggestions[] = $variant;
			}
		}
		return array_unique($suggestions);
	}

	function addWord($word)
	{
		//pspell
		if ($this->pspell)
		{
			if ($this->lang == 'ru')
				$word = convertCharsetInKOI8($word);
			if (!pspell_add_to_personal($this->pspell_link, $word))
				return false;
			if (!pspell_save_wordlist($this->pspell_link))
				return false;
		//custom
		}
		elseif($this->custom_spell)
		{
			if ($this->lang == 'ru')
				$word = convertCharsetInWIN1251($word);
			$path = $this->dic_path.'/dics/'.$this->lang.'_';

			$letter = $this->codeLetter(string_lower($word{0}));
			$path .= $letter.'.dic';
			if (!$handle = fopen($path, 'a'))
				return false;
			if (fwrite($handle, $word."\n") === FALSE)
				return false;
			fclose($handle);
			return true;
		}
	}
};


function convertCharsetInKOI8($word)
{
	return $GLOBALS["APPLICATION"]->ConvertCharset($word, "UTF-8", "KOI8-R");
}

function convertCharsetFromKOI8($word)
{
	return $GLOBALS["APPLICATION"]->ConvertCharset($word, "KOI8-R", "Windows-1251");
}

function convertCharsetInWIN1251($word)
{
	return $GLOBALS["APPLICATION"]->ConvertCharset($word, "UTF-8", "Windows-1251");
}

function convertCharsetFromWIN1251($word)
{
	return $GLOBALS["APPLICATION"]->ConvertCharset($word, "Windows-1251", "UTF-8");
}

function string_lower($text)
{
	$text = str_replace ('Я','я',$text);
	$text = str_replace ('Ч','ч',$text);
	$text = strtolower($text);
	return($text);
}

?>