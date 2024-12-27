<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\Visit;

use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDownloadProgressHandlerInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

class DownloadGeoLiteDbCommand extends Command implements GeolocationDownloadProgressHandlerInterface
{
    public const string NAME = 'visit:download-db';

    private ProgressBar|null $progressBar = null;
    private SymfonyStyle $io;

    public function __construct(private readonly GeolocationDbUpdaterInterface $dbUpdater)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription(
                'Checks if the GeoLite2 db file is too old or it does not exist, and tries to download an up-to-date '
                . 'copy if so.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        try {
            $result = $this->dbUpdater->checkDbUpdate($this);

            if ($result === GeolocationResult::LICENSE_MISSING) {
                $this->io->warning('It was not possible to download GeoLite2 db, because a license was not provided.');
                return ExitCode::EXIT_WARNING;
            }

            if ($result === GeolocationResult::MAX_ERRORS_REACHED) {
                $this->io->warning('Max consecutive errors reached. Cannot retry for a couple of days.');
                return ExitCode::EXIT_WARNING;
            }

            if ($result === GeolocationResult::UPDATE_IN_PROGRESS) {
                $this->io->warning('A geolocation db is already being downloaded by another process.');
                return ExitCode::EXIT_WARNING;
            }

            if ($this->progressBar === null) {
                $this->io->info('GeoLite2 db file is up to date.');
            } else {
                $this->progressBar->finish();
                $this->io->success('GeoLite2 db file properly downloaded.');
            }

            return ExitCode::EXIT_SUCCESS;
        } catch (GeolocationDbUpdateFailedException $e) {
            return $this->processGeoLiteUpdateError($e, $this->io);
        }
    }

    private function processGeoLiteUpdateError(GeolocationDbUpdateFailedException $e, SymfonyStyle $io): int
    {
        $olderDbExists = $e->olderDbExists;

        if ($olderDbExists) {
            $io->warning(
                'GeoLite2 db file update failed. Visits will continue to be located with the old version.',
            );
        } else {
            $io->error('GeoLite2 db file download failed. It will not be possible to locate visits.');
        }

        if ($io->isVerbose()) {
            $this->getApplication()?->renderThrowable($e, $io);
        }

        return $olderDbExists ? ExitCode::EXIT_WARNING : ExitCode::EXIT_FAILURE;
    }

    public function beforeDownload(bool $olderDbExists): void
    {
        $this->io->text(sprintf('<fg=blue>%s GeoLite2 db file...</>', $olderDbExists ? 'Updating' : 'Downloading'));
        $this->progressBar = new ProgressBar($this->io);
    }

    public function handleProgress(int $total, int $downloaded, bool $olderDbExists): void
    {
        $this->progressBar?->setMaxSteps($total);
        $this->progressBar?->setProgress($downloaded);
    }
}
