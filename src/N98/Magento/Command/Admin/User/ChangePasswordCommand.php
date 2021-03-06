<?php

namespace N98\Magento\Command\Admin\User;

use Exception;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePasswordCommand extends AbstractAdminUserCommand
{
    protected function configure()
    {
        $this
            ->setName('admin:user:change-password')
            ->addArgument('username', InputArgument::OPTIONAL, 'Username')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password')
            ->setDescription('Changes the password of a adminhtml user.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) {
            return;
        }

        /** @var $dialog DialogHelper */
        $dialog = $this->getHelper('dialog');

        // Username
        if (($username = $input->getArgument('username')) == null) {
            $username = $dialog->ask($output, '<question>Username:</question>');
        }

        $user = $this->userModel->loadByUsername($username);
        if ($user->getId() <= 0) {
            $output->writeln('<error>User was not found</error>');
            return;
        }

        // Password
        if (($password = $input->getArgument('password')) == null) {
            $password = $dialog->ask($output, '<question>Password:</question>');
        }

        try {
            // @see \Magento\Framework\Session\SessionManager::isSessionExists Hack to prevent session problems
            @session_start();

            $result = $user->validate();
            if (is_array($result)) {
                throw new Exception(implode(PHP_EOL, $result));
            }
            $user->setPassword($password);
            $user->setForceNewPassword(true);
            $user->save();
            $output->writeln('<info>Password successfully changed</info>');
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
