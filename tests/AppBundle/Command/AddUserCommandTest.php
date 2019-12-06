<?php

namespace Tests\Command;

use AppBundle\Command\AddUserCommand;
use AppBundle\Entity\User;
use AppBundle\Utils\Validator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddUserCommandTest extends KernelTestCase
{

    private $userData = [
        'username' => 'vasya_pupkin',
        'password' => 'imrealman',
        'email' => 'vasya@pupkin.gru',
        'name' => 'Vasiliy',
        'surname' => 'Pupen',
    ];

    protected function setUp()
    {
        exec('stty 2>&1', $output, $exitcode);
        $isSttySupported = 0 === $exitcode;

        $isWindows = '\\' === DIRECTORY_SEPARATOR;

        if ($isWindows || !$isSttySupported) {
            $this->markTestSkipped('`stty` is required to test this command.');
        }
    }

    /**
     * @dataProvider isAdminDataProvider
     */
    public function testCreateUserNonInteractive($isAdmin)
    {
        $input = $this->userData;
        if ($isAdmin) {
            $input['--admin'] = 1;
        }
        $this->executeCommand($input);

        $this->assertUserCreated($isAdmin);
    }

    /**
     * @dataProvider isAdminDataProvider
     */
    public function testCreateUserInteractive($isAdmin)
    {
        $this->executeCommand(
            $isAdmin ? ['--admin' => 1] : [],
            array_values($this->userData)
        );

        $this->assertUserCreated($isAdmin);
    }

    public function isAdminDataProvider()
    {
        yield [false];
        yield [true];
    }

    /**
     * check that the user was correctly created and saved in the db.
     */
    private function assertUserCreated($isAdmin)
    {
        $container = self::$kernel->getContainer();

        /** @var User $user */
        $user = $container->get('doctrine')->getRepository(User::class)->findOneByEmail($this->userData['email']);
        $this->assertNotNull($user);

        $this->assertSame($this->userData['name'], $user->getName());
        $this->assertSame($this->userData['surname'], $user->getSurname());
        $this->assertSame($this->userData['username'], $user->getUsername());
        $rez = $container->get('security.password_encoder')->isPasswordValid($user, $this->userData['password']);
        $this->assertTrue($rez);
        $this->assertSame($isAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER'], $user->getRoles());
    }

    /**
     * This helper method abstracts the boilerplate code needed to test the
     * execution of a command.
     */
    private function executeCommand(array $arguments, array $inputs = [])
    {
        self::bootKernel();

        $container = self::$kernel->getContainer();
        $command = new AddUserCommand(
            $container->get('doctrine')->getManager(),
            $container->get('security.password_encoder'),
            new Validator()
        );
        $command->setApplication(new Application(self::$kernel));

        $commandTester = new CommandTester($command);
        $commandTester->setInputs($inputs);
        $commandTester->execute($arguments);
    }
}
