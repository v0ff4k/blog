<?php

namespace AppBundle\Twig;

use AppBundle\Helper\UserHelper;
use AppBundle\Utils\Markdown;
use Symfony\Component\Intl\Intl;
use \Twig_SimpleFilter;
use \Twig_SimpleFunction;

/**
 * Extend Twig for some function/filters into HTML contents inside Twig templates.
 */
class AppExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var Markdown $parser*/
    private $parser;

    /** @var string $locales - string, separated by pipe: '|' */
    private $locales;

    /** @var string $locale - string, of current locale */
    private $locale;


    public function __construct(Markdown $parser, $locales)
    {
        $this->parser = $parser;
        $this->locales = $locales;// possible values of locales
        $this->locale = empty(UserHelper::getContainer()->get('request_stack')->getCurrentRequest())
                ? ''
                : UserHelper::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();// app.request.locale
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function getGlobals()//global variables {{ locale }}
    {
        return [
            'userSession' => $_COOKIE,// [[token], [PHPSESSID] ]
            'locale' => $this->locale,
            'uHelper' => UserHelper::that(),
            '_csrf_ajax' => UserHelper::getValueCsrfTokenManager('ajax'),
            'token' => UserHelper::getInstance()->getToken()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()//inject filters  {{ |md2html}}
    {
        return [
            new Twig_SimpleFilter('md2html', [$this, 'markdownToHtml'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('hideEmail', [$this, 'hideEmail']),
            new \Twig_SimpleFilter('ratingNew', [$this, 'ratingNew'], ['is_safe' => array('all')]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()//inject functions {% locales() == [ 0[[code],[name]] ] %}
    {
        return [
            new Twig_SimpleFunction('locales', [$this, 'getLocales']),
        ];
    }

    /**
     * markdownToHtml - Transform the given "Markdown" content into HTML content.
     *
     *  @param string $content
     * @return string
     */
    public function markdownToHtml($content)
    {
        return $this->parser->toHtml($content);
    }

    /**
     * getLocales get list of codes of the locales (languages)
     *
     * @return array
     */
    public function getLocales()
    {
        $localeCodes = explode('|', $this->locales);

        $locales = [];
        foreach ($localeCodes as $localeCode) {
            $locales[] = [
                'code' => $localeCode,
                'name' => Intl::getLocaleBundle()->getLocaleName($localeCode, $localeCode)
            ];
        }

        return $locales;
    }

    /**
     * hideEmail - micro method for inject from any part of system
     * convert vasya_pupkin@some.mail.com  >> vasya.***@....com
     *
     * @param string $string
     * @return string
     */
    public function hideEmail($string)
    {

        return UserHelper::hideEmail($string);
    }

    /**
     * using name and surname for proper display
     *
     * @param $surname
     * @return string
     */
    public function shortSurname($surname)
    {
        return '' . $surname[0] . '. ';
    }

    /**
     * Override bugle star rating funstion and template
     *
     * @param $number
     * @param int $max
     * @param string $starSize
     * @return mixed
     * @throws \Twig\Error\Error
     */
    public function ratingNew($number, $max = 5, $starSize = "")
    {
        return UserHelper::getContainer()->get('templating')->render(
            '@App/StarRating/Display/ratingDisplay.html.twig',
            array(
                'stars' => $number,
                'max' => $max,
                'starSize' => $starSize
            )
        );
    }
}
