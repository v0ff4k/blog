<?php

namespace AppBundle\Utils;

class Validator
{
    /**
     * @param string $username
     * @return mixed
     * @throws \Exception
     */
    public function validateUsername($username)
    {
        if (empty($username)) {
            throw new \Exception('The username can not be empty.');
        }

        if (mb_strlen(trim($username)) < 4) {
            throw new \Exception('The username must be at least 4 characters long.');
        }

        if (mb_strlen(trim($username)) > 32) {
            throw new \Exception('The username must be not so long, less 32 characters long.');
        }


        if (1 !== preg_match('/^[a-z_\.]+$/', $username)) {
            throw new \Exception('The username must contain only lowercase latin characters, underscores and dots.');
        }

        return $username;
    }

    /**
     * @param $plainPassword
     * @return mixed
     * @throws \Exception
     */
    public function validatePassword($plainPassword)
    {
        if (empty($plainPassword)) {
            throw new \Exception('The password can not be empty.');
        }

        if (mb_strlen(trim($plainPassword)) < 8) {
            throw new \Exception('The password must be at least 8 characters long.');
        }

        if (mb_strlen(trim($plainPassword)) > 32) {
            throw new \Exception('The password must be not so long, less 32 characters long.');
        }

        return $plainPassword;
    }

    /**
     * @param $email
     * @return mixed
     * @throws \Exception
     */
    public function validateEmail($email)
    {
        if (empty($email)) {
            throw new \Exception('The email can not be empty.');
        }

        if (false === mb_strpos($email, '@')) {
            throw new \Exception('The email should look like a real email.');
        }

        return $email;
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function validateName($name)
    {
        if (empty($name)) {
            throw new \Exception('The user\'s name can not be empty.');
        }

        return $name;
    }

    /**
     * @param $surname
     * @return mixed
     * @throws \Exception
     */
    public function validateSurname($surname)
    {
        if (empty($surname)) {
            throw new \Exception('The user\'s surname can not be empty.');
        }

        return $surname;
    }

    /**
     * @param string $fullName
     * @return mixed
     * @throws \Exception
     */
    public function validateFullName($fullName)
    {
        if (empty($fullName)) {
            throw new \Exception('The full name can not be empty.');
        }

        return $fullName;
    }
}
