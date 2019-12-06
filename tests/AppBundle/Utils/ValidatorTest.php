<?php

/*
 * Standard test for comparing validators.
 */

namespace Tests\AppBundle\Utils;

use AppBundle\Utils\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    /**
     * ValidatorTest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->object = new Validator();
    }

    /**
     * @throws \Exception
     */
    public function testValidateUsername()
    {
        $test = 'username';

        $this->assertSame($test, $this->object->validateUsername($test));
    }

    /**
     * @throws \Exception
     */
    public function testValidateUsernameEmpty()
    {
        $this->expectException('The username can not be empty.');
        $this->object->validateUsername(null);
    }

    /**
     * @throws \Exception
     */
    public function testValidateUsernameInvalid()
    {
        $this->expectException('The username must contain only lowercase latin characters, underscores and dots.');
        $this->object->validateUsername('INVALID');
    }

    /**
     * @throws \Exception
     */
    public function testValidatePassword()
    {
        $test = 'password';

        $this->assertSame($test, $this->object->validatePassword($test));
    }

    /**
     * @throws \Exception
     */
    public function testValidatePasswordEmpty()
    {
        $this->expectException('The password can not be empty.');
        $this->object->validatePassword(null);
    }

    /**
     * @throws \Exception
     */
    public function testValidatePasswordInvalid()
    {
        $this->expectException('The password must be at least 8 characters long.');
        $this->object->validatePassword('12345');
    }

    /**
     * @throws \Exception
     */
    public function testValidateEmail()
    {
        $test = '@';

        $this->assertSame($test, $this->object->validateEmail($test));
    }

    /**
     * @throws \Exception
     */
    public function testValidateEmailEmpty()
    {
        $this->expectException('The email can not be empty.');
        $this->object->validateEmail(null);
    }

    /**
     * @throws \Exception
     */
    public function testValidateEmailInvalid()
    {
        $this->expectException('The email should look like a real email.');
        $this->object->validateEmail('invalid');
    }

    public function testValidateFullName()
    {
        $test = 'Full Name';

        $this->assertSame($test, $this->object->validateFullName($test));
    }

    /**
     * @throws \Exception
     */
    public function testValidateEmailFullName()
    {
        $this->expectException('The full name can not be empty.');
        $this->object->validateFullName(null);
    }
}
