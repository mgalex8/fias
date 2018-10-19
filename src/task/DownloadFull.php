<?php

declare(strict_types=1);

namespace marvin255\fias\task;

use marvin255\fias\state\StateInterface;
use marvin255\fias\service\fias\InformerInterface;
use marvin255\fias\service\downloader\DownloaderInterface;
use marvin255\fias\service\filesystem\DirectoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Задача для загрузки архива с полной версией ФИАС.
 */
class DownloadFull extends AbstractTask
{
    /**
     * @var \marvin255\fias\service\fias\InformerInterface
     */
    protected $informer;
    /**
     * @var \marvin255\fias\service\downloader\DownloaderInterface
     */
    protected $downloader;
    /**
     * @var \marvin255\fias\service\filesystem\DirectoryInterface
     */
    protected $workDir;

    /**
     * @param \marvin255\fias\service\fias\InformerInterface         $informer
     * @param \marvin255\fias\service\downloader\DownloaderInterface $downloader
     * @param \marvin255\fias\service\filesystem\DirectoryInterface  $workDir
     * @param \Psr\Log\LoggerInterface                               $logger
     */
    public function __construct(InformerInterface $informer, DownloaderInterface $downloader, DirectoryInterface $workDir, LoggerInterface $logger = null)
    {
        $this->informer = $informer;
        $this->downloader = $downloader;
        $this->workDir = $workDir;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function run(StateInterface $state)
    {
        $this->info('Fetching archive url from fias information service');
        $informerResult = $this->informer->getCompleteInfo();

        if ($informerResult->hasResult()) {
            $this->info('Url fetched: ' . $informerResult->getUrl());
            $file = $this->workDir->createChildFile('archive.rar');
            $this->info('Downloading file from ' . $informerResult->getUrl() . ' to ' . $file->getPath());
            $this->downloader->download($informerResult->getUrl(), $file);
            $this->info('Downloading complete ' . $file->getPath());
            $state->setParameter('informerResult', $informerResult);
            $state->setParameter('archive', $file);
        } else {
            $this->info('Empty response');
            $state->complete();
        }
    }
}
