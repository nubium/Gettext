<?php
namespace Gettext;

use Exception;
use Gettext\Generators\PhpArray;
use Gettext\Utils\Plural;

class Translator
{
    private $dictionary = array();
    private $domain = 'messages';
    private $context_glue = "\004";
    private $plural = null;

    public function __construct()
    {
        // default plural
        $this->plural = new Plural(2, 'return ($n != 1);');
    }

    /**
     * Loads translation from a Translations instance, a file on an array
     *
     * @param Translations|string|array $translations
     *
     * @return Translator
     */
    public function loadTranslations($translations)
    {
        if (is_object($translations) && $translations instanceof Translations) {
            $this->loadArray(PhpArray::toArray($translations));
        } elseif (is_string($translations) && is_file($translations)) {
            $this->loadArray(include $translations);
        } elseif (is_array($translations)) {
            $this->loadArray($translations);
        } else {
            throw new \InvalidArgumentException('Invalid Translator: only arrays, files or instance of Translations are allowed');
        }

        return $this;
    }

    /**
     * Loads translations from an array
     *
     * @param array $translations
     */
    protected function loadArray(array $translations)
    {
        $domain = isset($translations['messages']['']['domain']) ? $translations['messages']['']['domain'] : null;
        $plural = null;

        // If a plural form is set we extract those values
        if (isset($translations['messages']['']['plural-forms'])) {
            $plural = Plural::createFromCode($translations['messages']['']['plural-forms']);
        }

        unset($translations['messages']['']);
        $this->addTranslations($translations['messages'], $domain, $plural);
    }

    /**
     * Set new translations to the dictionary
     *
     * @param array $translations
     * @param null|string $domain
     * @param Plural $plural Plural of translations. If not set, we use default one. If set, all keys which not overrided by new translations use old plural
     */
    public function addTranslations(array $translations, $domain = null, Plural $plural = null)
    {
        if ($domain === null) {
            $domain = $this->domain;
        }

        if (!isset($this->dictionary[$domain])) {
            $this->dictionary[$domain] = $translations;
        } else {
            $this->dictionary[$domain] = array_replace($this->dictionary[$domain], $translations);

            if ($plural != null) {
                $diffKeys = array_diff_key($this->dictionary[$domain], $translations);

                // Exchange default plural ... New messages replaces old messages
                foreach ($diffKeys as $key => $val) {
                    $this->dictionary[$domain][$key]['_plural'] = $this->plural;
                }
            }
        }

        if ($plural != null) {
            // Setup new default plural
            $this->plural = $plural;
        }
    }

    /**
     * Clear all translations
     */
    public function clearTranslations()
    {
        $this->dictionary = array();
    }

    /**
     * Search and returns a translation
     *
     * @param string $domain
     * @param string $context
     * @param string $original
     *
     * @return array
     */
    public function getTranslation($domain, $context, $original)
    {
        $key = isset($context) ? $context.$this->context_glue.$original : $original;

        return isset($this->dictionary[$domain][$key]) ? $this->dictionary[$domain][$key] : false;
    }

    /**
     * Gets a translation using the original string
     *
     * @param string $original
     *
     * @return string
     */
    public function gettext($original)
    {
        return $this->dpgettext($this->domain, null, $original);
    }

    /**
     * Gets a translation checking the plural form
     *
     * @param string $original
     * @param string $plural
     * @param string $value
     *
     * @return string
     */
    public function ngettext($original, $plural, $value)
    {
        return $this->dnpgettext($this->domain, null, $original, $plural, $value);
    }

    /**
     * Gets a translation checking the domain and the plural form
     *
     * @param string $domain
     * @param string $original
     * @param string $plural
     * @param string $value
     *
     * @return string
     */
    public function dngettext($domain, $original, $plural, $value)
    {
        return $this->dnpgettext($domain, null, $original, $plural, $value);
    }

    /**
     * Gets a translation checking the context and the plural form
     *
     * @param string $context
     * @param string $original
     * @param string $plural
     * @param string $value
     *
     * @return string
     */
    public function npgettext($context, $original, $plural, $value)
    {
        return $this->dnpgettext($this->domain, $context, $original, $plural, $value);
    }

    /**
     * Gets a translation checking the context
     *
     * @param string $context
     * @param string $original
     *
     * @return string
     */
    public function pgettext($context, $original)
    {
        return $this->dpgettext($this->domain, $context, $original);
    }

    /**
     * Gets a translation checking the domain
     *
     * @param string $domain
     * @param string $original
     *
     * @return string
     */
    public function dgettext($domain, $original)
    {
        return $this->dpgettext($domain, null, $original);
    }

    /**
     * Gets a translation checking the domain and context
     *
     * @param string $domain
     * @param string $context
     * @param string $original
     *
     * @return string
     */
    public function dpgettext($domain, $context, $original)
    {
        $translation = $this->getTranslation($domain, $context, $original);

        if (isset($translation[1]) && $translation[1] !== '') {
            return $translation[1];
        }

        return $original;
    }

    /**
     * Gets a translation checking the domain, the context and the plural form
     *
     * @param string $domain
     * @param string $context
     * @param string $original
     * @param string $plural
     * @param string $value
     */
    public function dnpgettext($domain, $context, $original, $plural, $value)
    {
        $translation = $this->getTranslation($domain, $context, $original);
        $key = $this->isPlural($value, (isset($translation['_plural']) ? $translation['_plural'] : null));

        if (isset($translation[$key]) && $translation[$key] !== '') {
            return $translation[$key];
        }

        return ($key === 1) ? $original : $plural;
    }

    /**
     * Executes the plural decision code given the number to decide which
     * plural version to take.
     *
     * @param  string $n
     * @param  Plural $plural Plural (if not set, used default plural = 0)
     * @return int
     */
    public function isPlural($n, Plural $plural = null)
    {
        if ($plural == null && $this->plural != null) {
            $plural = $this->plural;
        } else if ($plural == null) {
            return false;
        }

        return $plural->isPlural($n);
    }
}
