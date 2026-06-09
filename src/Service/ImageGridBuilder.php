<?php

declare(strict_types=1);

namespace Forumify\Discord\Service;

use League\Flysystem\FilesystemOperator;

class ImageGridBuilder
{
    private int $columns = 3;
    private int $cellWidth = 128;
    private int $cellHeight = 128;
    private int $padding = 16;

    /** @var list<array{storage: FilesystemOperator, path: string}> */
    private array $items = [];

    public function setColumns(int $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function setCellWidth(int $width): static
    {
        $this->cellWidth = $width;
        return $this;
    }

    public function setCellHeight(int $height): static
    {
        $this->cellHeight = $height;
        return $this;
    }

    public function setCellSize(int $width, int $height): static
    {
        $this->cellWidth = $width;
        $this->cellHeight = $height;
        return $this;
    }

    public function setPadding(int $padding): static
    {
        $this->padding = $padding;
        return $this;
    }

    public function addImage(FilesystemOperator $storage, string $path): static
    {
        $this->items[] = ['storage' => $storage, 'path' => $path];
        return $this;
    }

    /**
     * Build a webp composite image from all added images.
     * Returns the raw webp binary, or null if no images were added.
     */
    public function build(): ?string
    {
        if (empty($this->items)) {
            return null;
        }

        $count = count($this->items);
        $rows = (int)ceil($count / $this->columns);
        $canvasWidth = $this->cellWidth * $this->columns + $this->padding * ($this->columns - 1);
        $canvasHeight = $this->cellHeight * $rows + $this->padding * max(0, $rows - 1);

        $canvas = imagecreatetruecolor(max(1, $canvasWidth), max(1, $canvasHeight));
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefill($canvas, 0, 0, $transparent);
        }
        imagealphablending($canvas, true);

        foreach ($this->items as $index => $item) {
            $col = $index % $this->columns;
            $row = (int)floor($index / $this->columns);

            $x = $col * ($this->cellWidth + $this->padding);
            $y = $row * ($this->cellHeight + $this->padding);

            $this->drawCell($canvas, $item['storage'], $item['path'], $x, $y);
        }

        ob_start();
        imagewebp($canvas, null, 90);
        return (string)ob_get_clean();
    }

    private function drawCell(\GdImage $canvas, FilesystemOperator $storage, string $path, int $x, int $y): void
    {
        $source = $this->loadImage($storage, $path);
        if ($source === null) {
            return;
        }

        $originalWidth = imagesx($source);
        $originalHeight = imagesy($source);

        $scale = min(
            $this->cellWidth / $originalWidth,
            $this->cellHeight / $originalHeight,
        );

        $newWidth = max(1, (int)round($originalWidth * $scale));
        $newHeight = max(1, (int)round($originalHeight * $scale));

        // Center within cell
        $offsetX = $x + (int)round(($this->cellWidth - $newWidth) / 2);
        $offsetY = $y + (int)round(($this->cellHeight - $newHeight) / 2);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagesavealpha($resized, true);
        imagealphablending($resized, false);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefill($resized, 0, 0, $transparent);
        }
        imagealphablending($resized, true);

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        imagecopy($canvas, $resized, $offsetX, $offsetY, 0, 0, $newWidth, $newHeight);
    }

    private function loadImage(FilesystemOperator $storage, string $path): ?\GdImage
    {
        try {
            $data = $storage->read($path);
        } catch (\Throwable) {
            return null;
        }

        $image = @imagecreatefromstring($data);
        return $image !== false ? $image : null;
    }
}
