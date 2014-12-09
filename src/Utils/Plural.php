<?php
namespace Gettext\Utils;

class Plural
{
	protected $pluralCount;
	protected $pluralFunction;
	protected $pluralCode = 'return ($n != 1);';


	public function __construct($count, $code = null)
	{
        if ($code != null) {
            $this->pluralCode = $code;
        }

		$this->pluralCount = $count;
	}

	public function isPlural($n)
	{
		if (!$this->pluralFunction) {
			$this->pluralFunction = create_function('$n', self::fixTerseIfs($this->pluralCode));
		}

		if ($this->pluralCount <= 2) {
			return (call_user_func($this->pluralFunction, $n)) ? 2 : 1;
		}

		// We need to +1 because while (GNU) gettext codes assume 0 based,
		// this gettext actually stores 1 based.
		return (call_user_func($this->pluralFunction, $n)) + 1;
	}

    public static function createFromCode($code)
    {
        list($count, $code) = explode(';', $code);
        return new static( (int) str_replace('nplurals=', '', $count), str_replace('plural=', 'return ', str_replace('n', '$n', $code)) . ';');
    }

	/**
	 * This function will recursively wrap failure states in brackets if they contain a nested terse if
	 *
	 * This because PHP can not handle nested terse if's unless they are wrapped in brackets.
	 *
	 * This code probably only works for the gettext plural decision codes.
	 *
	 * return ($n==1 ? 0 : $n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? 1 : 2);
	 * becomes
	 * return ($n==1 ? 0 : ($n%10>=2 && $n%10<=4 && ($n%100<10 || $n%100>=20) ? 1 : 2));
	 *
	 * @param  string $code  the terse if string
	 * @param  bool   $inner If inner is true we wrap it in brackets
	 * @return string A formatted terse If that PHP can work with.
	 */
	private static function fixTerseIfs($code, $inner = false)
	{
		/**
		 * (?P<expression>[^?]+)   Capture everything up to ? as 'expression'
		 * \?                      ?
		 * (?P<success>[^:]+)      Capture everything up to : as 'success'
		 * :                       :
		 * (?P<failure>[^;]+)      Capture everything up to ; as 'failure'
		 */
		preg_match('/(?P<expression>[^?]+)\?(?P<success>[^:]+):(?P<failure>[^;]+)/', $code, $matches);

		// If no match was found then no terse if was present
		if (!isset($matches[0])) {
			return $code;
		}

		$expression = $matches['expression'];
		$success    = $matches['success'];
		$failure    = $matches['failure'];

		// Go look for another terse if in the failure state.
		$failure = self::fixTerseIfs($failure, true);
		$code = $expression.' ? '.$success.' : '.$failure;

		if ($inner) {
			return "($code)";
		}

		// note the semicolon. We need that for executing the code.
		return "$code;";
	}
}
