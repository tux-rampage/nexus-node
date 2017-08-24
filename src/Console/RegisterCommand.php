<?php
/**
 * @author    Axel Helmert
 * @copyright Copyright (c) 2017 Axel Helmert
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 */

namespace Rampage\Nexus\Node\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Rampage\Nexus\Node\MasterConfigInterface;
use GuzzleHttp\ClientInterface as HttpClient;
use GuzzleHttp\Psr7\Request;
use Zend\Diactoros\Response\JsonResponse;
use Rampage\Nexus\Exception\RuntimeException;
use Zend\Stdlib\Parameters;

class RegisterCommand extends Command
{
    const ARGUMENT_URL = 'url';
    const ARGUMENT_TOKEN = 'token';
    const OPTION_NODE_URL = 'node-url';
    const OPTION_NODE_NAME = 'name';

    const DEFAULT_PORT = 10072;

    /**
     * @var MasterConfigInterface
     */
    private $masterConfig;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @param MasterConfigInterface $masterConfig
     */
    public function __construct(MasterConfigInterface $masterConfig, HttpClient $client)
    {
        parent::__construct('master:register');

        $defaultUrl = 'https://' . gethostname() . ':' . self::DEFAULT_PORT . '/';
        $this->masterConfig = $masterConfig;
        $this->client = $client;

        $this->addArgument(self::ARGUMENT_URL, InputArgument::REQUIRED, 'The url to the master server');
        $this->addArgument(self::ARGUMENT_TOKEN, InputArgument::REQUIRED, 'The registration token of the master server to add new nodes.');
        $this->addOption(self::OPTION_NODE_URL, 'u', InputOption::VALUE_REQUIRED, 'The communication url of this node, that the master server should use.', $defaultUrl);
        $this->addOption(self::OPTION_NODE_NAME, 'n', InputOption::VALUE_REQUIRED, 'The name of this node as populated to the master', gethostname());
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $masterUrl = $input->getArgument(self::ARGUMENT_URL);
        $requestData = [
            'name' => $input->getOption(self::OPTION_NODE_NAME),
            'url' => $input->getOption(self::OPTION_NODE_URL)
        ];

        $body = json_encode($requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $headers = [
            'Authorization' => $input->getArgument(self::ARGUMENT_TOKEN),
        ];

        $request = new Request('POST', rtrim($masterUrl) . '/node/register', $headers, $body);
        $response = $this->client->send($request);

        if (!$response instanceof JsonResponse) {
            throw new RuntimeException('Unexpected server response');
        }

        $data = new Parameters($response->getPayload());
        $this->masterConfig->create($data['id'], $data['secret'], $masterUrl, $data['masterSecret']);

        $output->writeln('<info>Node registered successfully</>');
    }
}
