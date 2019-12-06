<?php

namespace ApiBundle\Security;

use AppBundle\Helper\UserHelper;

class ApiFormValidator
{

    private static $formName = [
        //User
        "_name" => "/[A-ZА-ЯЁ]{1}[a-zа-яё]{3,30}$/",
        "_surname" => "/[A-ZА-ЯЁ]{1}[a-zа-яё]{3,30}$/",
        "_email" => "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i",
        "_username" => "/^[a-zа-яё0-9_!@#$%&]{3,16}$/iu",
        // STRONG password, must be  a-zA-Z0-9 + specSymbols
        "_password" => "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,20}$/",
        //Blog+Comment
        "_title" => "/\<\>/mi",
        "_preview" => "",
        "_content" => "",
        "_keywords" => "",
        "_description" => "",
        "_rating" => "/^[0-5]{1}$/"
        ];

    /**
     * @param $fieldName
     * @param $value
     * @return bool|string
     * @throws \Exception
     */
    public static function isValid($fieldName, $value)
    {
        //all _plainPassword and _password processed as one.
        if (false !== stripos($fieldName, 'password')) {
            $fieldName = '_password';
        }

        if (isset(self::$formName[$fieldName])) {
            //_username field can be validated as user's email _email
            if ('_username' == $fieldName) {
                $fieldName = (false === strpos($value, '@')) ? '_username' : '_email';
            }

            //regExp exist for current field
            if (!preg_match(self::$formName[$fieldName], $value, $matches, PREG_OFFSET_CAPTURE, 0)) {
                //invalid value for regExp
                UserHelper::getLogg()->error(
                    'Invalid value for field: ' . json_encode($fieldName) .
                    ' regexp: ' . self::$formName[$fieldName] .
                    ' valie: ' . $value
                );

                return UserHelper::getTrans()->trans('problem.invalid_field_please_check', [], 'api');
            } else {
                if (UserHelper::isDev()) {
                    UserHelper::getLogg()->info(
                        'valid field: ' . $fieldName .
                        ' ,regexp: ' . self::$formName[$fieldName] .
                        ' ,and value: ' . $value
                    );
                }

                return true;
            }
        } else {
            UserHelper::getLogg()->error('Unknown field, need implement: ' . json_encode($fieldName));

            return 'invalid field: ' . htmlspecialchars($fieldName);
        }
    }
}
