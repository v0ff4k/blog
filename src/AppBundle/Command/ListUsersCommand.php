<?php

namespace AppBundle\Command;

use AppBundle\Entity\User;
use AppBundle\Helper\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A console command that lists all the existing users.
 * class: ListUsersCommand
 *
 *     $ php bin/console app:list-users
 * -----------------------------------------------------
 * @package AppBundle\Command
 */
class ListUsersCommand extends Command
{
    /** @var \Doctrine\ORM\EntityManagerInterface  */
    private $em;

    /** @var \Swift_Mailer $mailer */
    private $mailer;

    /** @var string $emailSender */
    private $emailSender;

    /**
     * ListUsersCommand constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Swift_Mailer $mailer
     * @param $emailSender
     */
    public function __construct(EntityManagerInterface $em, \Swift_Mailer $mailer, $emailSender)
    {
        parent::__construct();

        $this->em = $em;
        $this->mailer = $mailer;
        $this->emailSender = $emailSender;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            // a good practice is to use the 'app:' prefix to group all your custom application commands
            ->setName('app:list-users')
            ->setDescription('Lists all the existing users')
            ->setHelp($this->getCommandHelp())
           // optional
            ->addOption(
                'max-results',
                null,
                InputOption::VALUE_OPTIONAL,
                'Limits the number of users listed',
                50
            )
            ->addOption(
                'send-to',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, the result is sent to the given email address'
            )
        ;
    }

    /**
     * Just help block
     *
     * @return string
     */
    private function getCommandHelp()
    {
        return <<<'EOT'
The <info>%command.name%</info> command lists all the users registered in the application:

  <info>php %command.name%</info>

By default the command only displays the 50 most recent users. Set the number of
results to display with the <comment>--max-results</comment> option:

  <info>php %command.name%</info> <comment>--max-results=2000</comment>

In addition to displaying the user list, you can also send this information to
the email address specified in the <comment>--send-to</comment> option:

  <info>php %command.name%</info> <comment>--send-to=bot@local_serv.com</comment>


EOT;
    }

    /**
     * This method is executed after interact() and initialize().
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $maxResults = $input->getOption('max-results');
        $users = $this->em->getRepository(User::class)->findBy([], ['id' => 'DESC'], $maxResults);

        $usersAsPlainArrays = array_map(function (User $user) {
            return [
                $user->getId(),
                $user->getName(),
                $user->getSurname(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getStringRoles()
            ];
        }, $users);

        // to send the list of users via email with the '--send-to' option
        $bufferedOutput = new BufferedOutput();
        $io = new SymfonyStyle($input, $bufferedOutput);
        $io->table(
            ['ID', 'Name', 'Surname', 'Username', 'Email', 'Roles'],
            $usersAsPlainArrays
        );

        // instead of just displaying the table of users, store its contents in a variable
        $usersAsATable = $bufferedOutput->fetch();
        $output->write($usersAsATable);

        if (null !== $email = $input->getOption('send-to')) {
            $this->sendReport($usersAsATable, $email);
        }
    }

    /**
     * Sends the given $contents to the $recipient email address.
     *
     * @param string $contents
     * @param string $recipient
     */
    private function sendReport($contents, $recipient)
    {
        $message = $this->mailer->createMessage()
            ->setSubject(sprintf('app:list-users report (%s)', date('Y-m-d H:i:s')))
            ->setFrom($this->emailSender)
            ->setTo($recipient)
            ->setBody($contents, 'text/plain')
        ;

        UserHelper::getLogg()->info(
            'Send Report to:' . (string) $recipient .
            ', with content: ' . json_encode(strip_tags($contents))
        );
        $this->mailer->send($message);
    }
}
