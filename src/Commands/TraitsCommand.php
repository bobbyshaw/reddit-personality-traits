<?php

namespace Bobbyshaw\RedditPersonalities\Commands;

use Bobbyshaw\RedditPersonalities\TraitsService;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use League\Csv\Reader;

class TraitsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('traits:check')
            ->setDescription('Retrieve traits information')
            ->addArgument(
                'comments-file',
                InputArgument::REQUIRED,
                'File of users and comments to check'
            )
            ->addArgument(
                'output-file',
                InputArgument::REQUIRED,
                'File to write traits output'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getArgument('comments-file');
        $reader = Reader::createFromPath($inputFile);

        $outputFile = $input->getArgument('output-file');
        $writer = Writer::createFromPath($outputFile, 'w+');

        $rows = $reader->setOffset(1)->fetchAll();

        $comments = "";
        foreach ($rows as $row) {
            $comments .= $row[0];
        }

        $traitsService = new TraitsService();

        $traits = $traitsService->getTraits($comments);

        if (empty($traits)) {
            return;
        }

        foreach ($traits->personality as $personalityCategory) {
            foreach ($personalityCategory->children as $personalityTrait) {
                $writer->insertOne([
                    $personalityCategory->category,
                    $personalityCategory->name,
                    $personalityTrait->name,
                    $personalityTrait->percentile
                ]);
            }
        }

        foreach ($traits->needs as $need) {
            $writer->insertOne([
                $need->category,
                $need->name,
                $need->percentile
            ]);
        }

        foreach ($traits->values as $value) {
            $writer->insertOne([
                $value->category,
                $value->name,
                $value->percentile
            ]);
        }
    }
}
