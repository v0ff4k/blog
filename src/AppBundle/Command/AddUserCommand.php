<?php

namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Utils\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * A console command that creates users and stores them in the database.
 * class: AddUserCommand
 *
 *     $ php bin/console app:add-user
 *     $ php bin/console app:add-user -vv
 * -----------------------------------------------------
 * @package AppBundle\Command
 */
class AddUserCommand extends Command
{
    const MAX_ATTEMPTS = 5;

    /** @var SymfonyStyle $io */
    private $io;

    /** @var \Doctrine\ORM\EntityManagerInterface  */
    private $em;

    /** @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $passwordEncoder */
    private $passwordEncoder;

    /** @var \AppBundle\Utils\Validator $validator */
    private $validator;

    /**
     * AddUserCommand constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface $encoder
     * @param \AppBundle\Utils\Validator $validator
     */
    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $encoder, Validator $validator)
    {
        parent::__construct();

        $this->em = $em;
        $this->passwordEncoder = $encoder;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:add-user')
            ->setDescription('Creates users and stores them in the database')
            ->setHelp($this->getCommandHelp())
            // options
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the new user')
            ->addArgument('surname', InputArgument::OPTIONAL, 'The surname of the new user')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user is created as an administrator');
    }

    /**
     * Just help block
     *
     * @return string
     */
    private function getCommandHelp()
    {
        return <<<'EOT'
The <info>%command.name%</info> command creates new users and saves them in the database:

  <info>php %command.name%</info> <comment>username password email</comment>

By default the command creates regular users. To create administrator users,
add the <comment>--admin</comment> option:

  <info>php %command.name%</info> username password email <comment>--admin</comment>

If you omit any of the three required arguments, the command will ask you to
provide the missing values:

  # command will ask you for the email
  <info>php %command.name%</info> <comment>username password</comment>

  # command will ask you for the email and password
  <info>php %command.name%</info> <comment>username</comment>

  # command will ask you for all arguments
  <info>php %command.name%</info>

EOT;
    }

    /**
     * init
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * interact with user
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getArgument('username') &&
            null !== $input->getArgument('password') &&
            null !== $input->getArgument('email') &&
            null !== $input->getArgument('name') &&
            null !== $input->getArgument('surname')
        ) {
            return;
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text(
            [
                'If you prefer to not use this interactive wizard, provide the',
                'arguments required by this command as follows:',
                '',
                ' $ php bin/console app:add-user username password email@example.com',
                '',
                'Now we\'ll ask you for the value of all the missing command arguments.',
            ]
        );

        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Username(login)</info>: '.$username);
        } else {
            $username = $this->io->ask('Username', null, [$this->validator, 'validateUsername']);
            $input->setArgument('username', $username);
        }

        // Ask for the password if it's not defined
        $password = $input->getArgument('password');
        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.str_repeat('*', mb_strlen($password)));
        } else {
            $password = $this->io->askHidden(
                'Password (your type will be hidden)',
                [$this->validator, 'validatePassword']
            );
            $input->setArgument('password', $password);
        }

        // Ask for the email if it's not defined
        $email = $input->getArgument('email');
        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, [$this->validator, 'validateEmail']);
            $input->setArgument('email', $email);
        }

        // Ask for the name if it's not defined
        $name = $input->getArgument('name');
        if (null !== $name) {
            $this->io->text(' > <info>Name</info>: '.$name);
        } else {
            $name = $this->io->ask('name', null, [$this->validator, 'validateName']);
            $input->setArgument('name', $name);
        }

        // Ask for the surname if it's not defined
        $surname = $input->getArgument('surname');
        if (null !== $surname) {
            $this->io->text(' > <info>Surname</info>: '.$surname);
        } else {
            $surname = $this->io->ask('surname', null, [$this->validator, 'validateSurname']);
            $input->setArgument('surname', $surname);
        }
    }

    /**
     * This method is executed after interact() and initialize().
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        $name = $input->getArgument('name');
        $surname = $input->getArgument('surname');
        $username = $input->getArgument('username');
        $plainPassword = $input->getArgument('password');
        $email = $input->getArgument('email');
        $isAdmin = $input->getOption('admin');

        // make sure to validate the user data is correct
        $this->validateUserData($username, $plainPassword, $email, $name, $surname);

        // create the user and encode its password
        $user = new User();
        $user->setName($name);
        $user->setSurname($surname);
        $user->setUsername($username);
        $user->setToken(md5(date('U')));
        $user->setEmail($email);
        $user->setRoles([$isAdmin ? 'ROLE_ADMIN' : 'ROLE_USER']);

        $encodedPassword = $this->passwordEncoder->encodePassword($user, $plainPassword);
        $user->setPassword($encodedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $this->io->success(
            sprintf(
                '%s was successfully created: %s (%s)',
                $isAdmin ? 'Administrator user' : 'User',
                $user->getUsername(),
                $user->getEmail()
            )
        );

        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {
            $this->io->comment(
                sprintf(
                    'New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB',
                    $user->getId(),
                    $event->getDuration(),
                    $event->getMemory() / pow(1024, 2)
                )
            );
        }
    }

    /**
     * @param $username
     * @param $plainPassword
     * @param $email
     * @param $name
     * @param $surname
     * @throws \Exception
     */
    private function validateUserData($username, $plainPassword, $email, $name, $surname)
    {
        $userRepository = $this->em->getRepository(User::class);

        // first check if a user with the same username already exists.
        $existingUser = $userRepository->findOneBy(['username' => $username]);

        if (null !== $existingUser) {
            throw new \RuntimeException(
                sprintf('There is already a user registered with the "%s" username.', $username)
            );
        }

        // validate password and email if is not this input means interactive.
        $this->validator->validatePassword($plainPassword);
        $this->validator->validateEmail($email);
        $this->validator->validateName($name);
        $this->validator->validateSurname($surname);

        // check if a user with the same email already exists.
        $existingEmail = $userRepository->findOneBy(['email' => $email]);

        if (null !== $existingEmail) {
            throw new \RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }
    }
}
