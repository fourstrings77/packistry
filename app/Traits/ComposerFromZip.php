<?php

declare(strict_types=1);

namespace App\Traits;

use App\Exceptions\ComposerJsonNotFoundException;
use App\Exceptions\FailedToOpenArchiveException;
use ZipArchive;

trait ComposerFromZip
{
    /**
     * @return array<string, mixed>
     *
     * @throws ComposerJsonNotFoundException|FailedToOpenArchiveException
     */
    private function decodedComposerJsonFromZip(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new FailedToOpenArchiveException("failed to open archive $path");
        }

        try {
            $index = $this->composerJsonIndexFromArchive($zip);

            $content = $zip->getFromIndex($index);

            if ($content === false) {
                return throw new ComposerJsonNotFoundException('composer.json not found in archive');
            }

            $decoded = json_decode($content, true);

            if (! is_array($decoded)) {
                return throw new ComposerJsonNotFoundException('composer.json not found in archive');
            }

            return $decoded;
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws ComposerJsonNotFoundException|FailedToOpenArchiveException
     */
    private function composerJsonIndexFromArchive(ZipArchive $zip): int
    {
        $rootIndex = $zip->locateName('composer.json');

        if ($rootIndex !== false) {
            return $rootIndex;
        }

        $topLevelDirectories = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false) {
                throw new FailedToOpenArchiveException('failed to determine archive entry name');
            }

            $name = trim($name, '/');

            if ($name === '' || ! str_contains($name, '/')) {
                continue;
            }

            [$directory] = explode('/', $name, 2);
            $topLevelDirectories[$directory] = true;
        }

        if (count($topLevelDirectories) !== 1) {
            throw new ComposerJsonNotFoundException('composer.json not found in archive');
        }

        $directory = array_key_first($topLevelDirectories);
        /** @var string $directory */
        $index = $zip->locateName($directory.'/composer.json');

        if ($index === false) {
            throw new ComposerJsonNotFoundException('composer.json not found in archive');
        }

        return $index;
    }
}
