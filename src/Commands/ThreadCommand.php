<?php

namespace Bobbyshaw\RedditPersonalities\Commands;

use Bobbyshaw\RedditPersonalities\TraitsService;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;

class ThreadCommand extends Command
{

    /**
     * @var array
     */
    protected $authorTopTraits;

    protected function configure()
    {
        $this
            ->setName('thread:annotate')
            ->setDescription('Annotate thread file with user traits')
            ->addArgument(
                'thread-file',
                InputArgument::REQUIRED,
                'File of comments thread'
            )
            ->addArgument(
                'annotated-thread-file',
                InputArgument::REQUIRED,
                'Output file of annotated comments thread'
            )
            ->addOption(
                'create-individual-trait-files',
                '-i',
                InputOption::VALUE_NONE,
                'Create individual personality file for each user'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getArgument('thread-file');
        $thread = file_get_contents(__DIR__ . "/../../" . $inputFile);

        $commentsAndAuthors = explode("%N%", $thread);

        $output = [];

        $comment = '';
        $author = '';

        $count = count($commentsAndAuthors);

        for ($i = 0; $i < $count-1; $i = $i + 2) {
            $comment = $commentsAndAuthors[$i];
            $author = $commentsAndAuthors[$i+1];

            if ($input->getOption('create-individual-trait-files')) {
                $this->createIndividualTraitFiles($author);
            }

            if (!isset($this->authorTopTraits[$author])) {
                $this->authorTopTraits[$author] = $this->getTopTraits($author);
            }

            $output[] = $comment;
            $output[] = $author;
            $output[] = implode(",", array_map(function($trait) {
                return $trait['name'];
            }, $this->authorTopTraits[$author]));
        }

        $outputFile = $input->getArgument('annotated-thread-file');
        $outputText = implode("%N%", $output);
        file_put_contents(__DIR__ . "/../../" . $outputFile, $outputText);
    }

    /**
     * @param string $author
     */
    protected function createIndividualTraitFiles($author)
    {
        $command = $this->getApplication()->find('traits:check');

        $inputFile = __DIR__ . "/../../data/$author";
        $outputFile = __DIR__ . "/../../data/{$author}_traits.csv";

        if (file_exists($outputFile)) {
            // Don't fetch traits if we already have;
            return;
        }

        $arguments = array(
            'command' => 'traits:check',
            'comments-file' => $inputFile,
            'output-file' => $outputFile
        );

        $input = new ArrayInput($arguments);
        $command->run($input, new NullOutput());
    }

    /**
     * @param $author
     * @return array $topTraits
     */
    protected function getTopTraits($author)
    {

        $comments = file_get_contents(__DIR__ . "/../../data/$author");

        $traitsService = new TraitsService();

        $traits = $traitsService->getTraits($comments);

        if (empty($traits)) {
            return [];
        }

        $topTraits = [];

        foreach ($traits->personality as $personalityCategory) {
            foreach ($personalityCategory->children as $personalityTrait) {

                if (!isset($topTraits['personality'])) {
                    $topTraits['personality'] = [
                        'name' => $personalityTrait->name,
                        'percentile' => $personalityTrait->percentile
                    ];
                } else {
                    if ($personalityTrait->percentile > $topTraits['personality']['percentile']) {
                        $topTraits['personality'] = [
                            'name' => $personalityTrait->name,
                            'percentile' => $personalityTrait->percentile
                        ];
                    }
                }
            }
        }

        foreach ($traits->needs as $need) {

            if (!isset($topTraits['needs'])) {
                $topTraits['needs'] = [
                    'name' => $need->name,
                    'percentile' => $need->percentile
                ];
            } else {
                if ($need->percentile > $topTraits['needs']['percentile']) {
                    $topTraits['needs'] = [
                        'name' => $need->name,
                        'percentile' => $need->percentile
                    ];
                }
            }
        }

        foreach ($traits->values as $value) {

            if (!isset($topTraits['values'])) {
                $topTraits['values'] = [
                    'name' => $value->name,
                    'percentile' => $value->percentile
                ];
            } else {
                if ($value->percentile > $topTraits['values']['percentile']) {
                    $topTraits['values'] = [
                        'name' => $value->name,
                        'percentile' => $value->percentile
                    ];
                }
            }
        }

        return $topTraits;
    }
}
