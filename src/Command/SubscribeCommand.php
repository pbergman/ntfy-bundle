<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\Command;

use PBergman\Ntfy\Model\Message;
use PBergman\Ntfy\Model\SubscribeParameters;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

class SubscribeCommand extends AbstractCommand
{
    protected static $defaultName = 'ntfy:subscribe';

    protected function configure()
    {
        $available = $this->getAvailable();

        $this->setDescription('Subscribe to a topic on a remote ntfy server.');
        $this->setHelp(<<<EOH
Subscribe to a topic on a remote ntfy server.

Available:        
     $available

EOH
);
        $this->addArgument('server', InputArgument::REQUIRED, 'The server config name where topic(s) are available');
        $this->addArgument('topic', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'The topic(s) to subscribe to');
        $this->addOption('since', null, InputOption::VALUE_REQUIRED, 'Return cached messages since timestamp, duration or message ID');
        $this->addOption('poll', null, InputOption::VALUE_NONE, 'Include scheduled/delayed messages in message list');
        $this->addOption('scheduled', null, InputOption::VALUE_NONE, 'Include scheduled/delayed messages in message list');
        $this->addOption('id', null, InputOption::VALUE_REQUIRED, 'Filter: Only return messages that match this exact message ID');
        $this->addOption('message', null, InputOption::VALUE_REQUIRED, 'Filter: Only return messages that match this exact message string');
        $this->addOption('title', null, InputOption::VALUE_REQUIRED, 'Filter: Only return messages that match this exact title string');
        $this->addOption('priority', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Filter: Only return messages that match any priority listed');
        $this->addOption('tags', null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Filter: Only return messages that match all listed tags');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server  = $this->getServers($input->getArgument('server'));
        $params  = $this->getSubscribeParameters($input);
        $styles  = new SymfonyStyle($input, $output);
        $width   = (new Terminal())->getWidth();

        foreach($server['client']->subscribe(\implode(',', $input->getArgument('topic')), $params) as $message) {
            $this->print($message, $width, $styles, $output->isVerbose());
        }

        return 0;
    }

    private function print(Message $message, int $width, SymfonyStyle $styles, bool $isVerbose)
    {
        $title  = $message->getTitle() ?? '';
        $date   = (new \DateTime('@'. $message->getTime()))->format('Y-m-d H:i:s');
        $length = \strlen($title);

        if ($length > ($width - 20)) {
            $title  = \substr($title, 0, ($width - 23)) . '...';
            $length = ($width - 20);
        }

        $styles->title(sprintf('%s%-' . ($width - $length - 20) .  's%s', $title, '', $date));
        $styles->block(wordwrap($message->getMessage(), $width, "\n", true));

        if ($isVerbose) {
            $list = [
                ['id' => $message->getId()],
                new TableSeparator(),
                ['topic' => $message->getTopic()],
                new TableSeparator(),
            ];

            if (null !== $event = $message->getEvent()) {
                $list[] = ['event' => $event];
                $list[] = new TableSeparator();
            }

            if (null !== $tags = $message->getTags()) {
                $list[] = ['tags' => \implode(', ', $tags)];
                $list[] = new TableSeparator();
            }

            if (null !== $priority = $message->getPriority()) {
                $list[] = ['priority' => $priority];
                $list[] = new TableSeparator();
            }

            if (null !== $click = $message->getClick()) {
                $list[] = ['click' => $click];
                $list[] = new TableSeparator();
            }

            if (null !== $actions = $message->getActions()) {
                $list[] = ['actions' => \implode("\n", \array_map('strval', $actions))];
                $list[] = new TableSeparator();
            }

            if (null !== $attachment = $message->getAttachment()) {
                $list[] = 'attachment';

                foreach($attachment as $key => $value) {
                    $list[] = [$key => $value];
                }

                $list[] = new TableSeparator();
            }

            \array_pop($list);

            $styles->definitionList(...$list);
        }
    }

    private function getSubscribeParameters(InputInterface $input): SubscribeParameters
    {
        $params = new SubscribeParameters();

        if (null !== $x = $input->getOption('since')) {
            $params->setSince($x);
        }
        if (null !== $x = $input->getOption('id')) {
            $params->setId($x);
        }
        if (null !== $x = $input->getOption('message')) {
            $params->setMessage($x);
        }
        if (null !== $x = $input->getOption('title')) {
            $params->setTitle($x);
        }
        if ([] !== $x = $input->getOption('tags')) {
            $params->setTags($x);
        }
        if ([] !== $x = $input->getOption('priority')) {
            $params->setPriority($x);
        }
        if (false !== $input->getOption('poll')) {
            $params->setPoll(true);
        }
        if (false !== $input->getOption('scheduled')) {
            $params->setScheduled(true);
        }

        return $params;
    }
}