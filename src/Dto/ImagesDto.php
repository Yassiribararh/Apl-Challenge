<?php

declare(strict_types=1);

namespace App\Dto;

final class ImagesDto
{
    public string $name;

    public string $path;

    public string $type;

    public bool $offlineMode;

    public function __construct(
        string $name,
        string $path,
        string $type,
        bool $offlineMode
    ) {
        $this->name = $name;
        $this->path = $path;
        $this->type = $type;
        $this->offlineMode = $offlineMode;
    }
}
