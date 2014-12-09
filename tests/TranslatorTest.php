<?php

/**
 * Class TranslatorTest
 *
 * Translator tests
 */
class TranslatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Gettext\Translator
     */
    protected $translator;

    public function setUp()
    {
        $this->translator = new \Gettext\Translator();
    }

    /**
     * Testing if more arrays with plurals loaded, message use right plural
     */
    public function testMorePlurals()
    {
        $langOne = ['messages' => [
                    '' => [
                            'domain' => 'messages',
                            'lang' => 'cs',
                            'plural-forms' => 'nplurals=3; plural=(n==1) ? 0 : ((n>=2 && n<=4) ? 1 : 2);',
                        ],
                    'test' => [
                        0 => 'test',
                        1 => 'zprava',
                        2 => 'zpravy',
                        3 => 'zprav',
                    ]
                ]];

        $langTwo = ['messages' => [
            '' => [
                    'domain' => 'messages',
                    'lang' => 'en',
                    'plural-forms' => 'nplurals=2; plural=(n != 1);',
                ],
            'test2' => [
                0 => 'test2',
                1 => 'foo',
                2 => 'foos',
            ]
        ]];

        $this->translator->loadTranslations($langOne);
        $this->translator->loadTranslations($langTwo);

        $this->assertEquals('zprava', $this->translator->ngettext('test', null, 1));
        $this->assertEquals('zpravy', $this->translator->ngettext('test', null, 2));
        $this->assertEquals('zprav', $this->translator->ngettext('test', null, 0));
        $this->assertEquals('zprav', $this->translator->ngettext('test', null, 5));
        $this->assertEquals('foo', $this->translator->ngettext('test2', null, 1));
        $this->assertEquals('foos', $this->translator->ngettext('test2', null, 2));
    }

    /**
     * Testing if more arrays with plurals loaded, message use right plural (with override)
     */
    public function testPluralOverride()
    {
        $langOne = ['messages' => [
            '' => [
                'domain' => 'messages',
                'lang' => 'cs',
                'plural-forms' => 'nplurals=3; plural=(n==1) ? 0 : ((n>=2 && n<=4) ? 1 : 2);',
            ],
            'test' => [
                0 => 'test',
                1 => 'zprava',
                2 => 'zpravy',
                3 => 'zprav',
            ]
        ]];

        $langTwo = ['messages' => [
            '' => [
                'domain' => 'messages',
                'lang' => 'en',
                'plural-forms' => 'nplurals=2; plural=(n != 1);',
            ],
            'test' => [
                0 => 'test2',
                1 => 'foo',
                2 => 'foos',
            ]
        ]];

        $this->translator->loadTranslations($langOne);
        $this->translator->loadTranslations($langTwo);

        $this->assertEquals('foos', $this->translator->ngettext('test', null, 0));
        $this->assertEquals('foo', $this->translator->ngettext('test', null, 1));
        $this->assertEquals('foos', $this->translator->ngettext('test', null, 2));
    }

}
