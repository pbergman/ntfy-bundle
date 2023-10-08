<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\Command;

use Symfony\Component\Console\Command\Command;

abstract class AbstractCommand extends Command
{
    private array $servers = [];

    public function __construct(array $servers)
    {
        $this->servers = $servers;
        parent::__construct();
    }

    protected function getServers(string $server): array
    {
        if (false === \array_key_exists($server, $this->servers)) {
            throw new \RuntimeException(sprintf('No server with name %s is registered. See help fot avaialable servers', $server));
        }

        return $this->servers[$server];
    }

    protected function getAvailable(): string
    {
        $available = '';

        foreach ($this->servers as $name => $stack){
            $available .= "\n     <comment>server</comment>: " . $name;
            $available .= "\n     <comment>topics</comment>: " . \implode(", ", \array_keys($stack['topics'] ?? []));
            $available .= "\n";
        }

        return $available;
    }
}