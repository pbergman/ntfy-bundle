<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\Command;

use PBergman\Ntfy\Encoding\Marshaller;
use PBergman\Ntfy\Exception\ErrorException;
use PBergman\Ntfy\Exception\PublishException;
use PBergman\Ntfy\Model\PublishParameters;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PublishCommand extends AbstractCommand
{
    protected static $defaultName = 'ntfy:publish';

    protected function configure()
    {
        $available = $this->getAvailable();

        $this->setDescription('Publish message to topic on a remote ntfy server.');
        $this->setHelp(<<<EOH
Publish message to topic on a remote ntfy server.

Available:        
     $available

To publish a simple message the following can be run:

php bin/console ntfy:publish --action='{"action": "view", "label": "Open website", "url": "https://example.com/", "clear": true}' example example-topic "Hello visit my new website"

This will publish message on server example to topic example-topic with a action and message, to include af file the following can be done

cat logfile | grep -i error | php bin/console ntfy:publish example example-topic - --filename="errors.txt" --title="error" --message="Errors summarise" --tag=warning --priority=5

EOH
        );

        $this->addArgument('server', InputArgument::REQUIRED, 'The server config name where topic(s) are available');
        $this->addArgument('topic', InputArgument::REQUIRED, 'The topic to publish to');
        $this->addArgument('body', InputArgument::OPTIONAL, 'The body for upload or message, use - to read from stdin');

        $this->addOption('message', null, InputOption::VALUE_REQUIRED, 'Main body of the message as shown in the notification');
        $this->addOption('title', null, InputOption::VALUE_REQUIRED, 'Message title');
        $this->addOption('tag', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Message tags');
        $this->addOption('priority', null, InputOption::VALUE_REQUIRED, 'Message priority (1 (low) till 5 (high))');
        $this->addOption('action', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Message action in JSON, for example: \'{"action": "view", "label": "Open portal", "url": "https://home.nest.com/", "clear": true}\'');
        $this->addOption('click', null, InputOption::VALUE_REQUIRED, 'URL to open when notification is clicked');
        $this->addOption('attach', null, InputOption::VALUE_REQUIRED, 'URL to send as an attachment, as an alternative to PUT/POST-ing an attachment');
        $this->addOption('markdown', null, InputOption::VALUE_NONE, 'Enable Markdown formatting in the notification body');
        $this->addOption('icon', null, InputOption::VALUE_REQUIRED, 'URL to use as notification icon');
        $this->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Optional attachment filename, as it appears in the client');
        $this->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Timestamp or duration for delayed delivery');
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'E-mail address for e-mail notifications');
        $this->addOption('call', null, InputOption::VALUE_REQUIRED, 'Phone number for phone calls');
        $this->addOption('cache', null, InputOption::VALUE_NONE, 'Allows disabling message caching');
        $this->addOption('firebase', null, InputOption::VALUE_NONE, 'Allows disabling sending to Firebase');
        $this->addOption('unified-push', null, InputOption::VALUE_NONE, 'UnifiedPush publish option, only to be used by UnifiedPush apps');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {
            $resp = $this->getServers($input->getArgument('server'))['client']->publish($input->getArgument('topic'), $this->getPublishParameters($input), $this->getBody($input));
            $output->writeln("Message successful published (<comment>" . $resp()->getId() . "</comment>)");
        } catch (PublishException $e) {

            $output->writeln($e->getMessage());

            if (($prev = $e->getPrevious()) && $prev instanceof ErrorException) {
                $output->writeln(sprintf('[%d] %e', $prev->getCode(), $prev->getMessage()));
            }

            return 1;
        }

        return 0;
    }

    private function getBody(InputInterface $input)
    {
        if (null !== $body = $input->getArgument('body')) {

            if ('-' === $body) {
                return stream_get_contents(STDIN);
            }

            return $body;
        }

        return null;
    }

    private function getPublishParameters(InputInterface $input): PublishParameters
    {
        $params = new PublishParameters();

        if (null !== $x = $input->getOption('message')) {
            $params->setMessage($x);
        }

        if (null !== $x = $input->getOption('title')) {
            $params->setTitle($x);
        }

        if ([] !== $x = $input->getOption('tag')) {
            $params->setTags($x);
        }

        if (null !== $x = $input->getOption('click')) {
            $params->setClick($x);
        }

        if (null !== $x = $input->getOption('priority')) {
            $params->setPriority((int)$x);
        }

        if ([] !== $x = $input->getOption('action')) {
            $marshaller = new Marshaller();
            $data       = ['actions' => []];
            foreach ($x as $action) {
                $data['actions'][] = \json_decode($action, true);
            }
            $params->setActions($marshaller->unmarshall($data)->getActions());
        }

        if (null !== $x = $input->getOption('attach')) {
            $params->setAttach($x);
        }

        if (false !== $x = $input->getOption('markdown')) {
            $params->setMarkdown($x);
        }

        if (null !== $x = $input->getOption('delay')) {
            $params->setDelay($x);
        }

        if (null !== $x = $input->getOption('icon')) {
            $params->setIcon($x);
        }

        if (null !== $x = $input->getOption('filename')) {
            $params->setFilename($x);
        }

        if (null !== $x = $input->getOption('email')) {
            $params->setEmail($x);
        }

        if (null !== $x = $input->getOption('call')) {
            $params->setCall($x);
        }

        if (false !== $x = $input->getOption('cache')) {
            $params->setCall($x);
        }

        if (false !== $x = $input->getOption('firebase')) {
            $params->setFirebase($x);
        }

        if (false !== $x = $input->getOption('unified-push')) {
            $params->setUnifiedPush($x);
        }

        return $params;
    }
}
