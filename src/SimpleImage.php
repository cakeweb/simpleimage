<?php

namespace CakeWeb;

class SimpleImage extends \claviska\SimpleImage
{
	const COLOR = [
		'red' => 255,
		'green' => 255,
		'blue' => 255,
		'alpha' => 127
	];

	protected $changed = false;

	protected function exec(callable $method, array $params): self
	{
		$beforeWidth = $this->getWidth();
		$beforeHeight = $this->getHeight();

		call_user_func_array($method, $params);

		$afterWidth = $this->getWidth();
		$afterHeight = $this->getHeight();
		if(!$this->changed && ($beforeWidth != $afterWidth || $beforeHeight != $afterHeight))
		{
			$this->changed = true;
		}

		return $this;
	}

	protected function _standardizeSize(int $width, int $height, array $color): self
	{
		$beforeWidth = $this->getWidth();
		$beforeHeight = $this->getHeight();
		if($beforeWidth != $width)
		{
			// Standardize width
			$this->resize($width, null);
			$currentHeight = $this->getHeight();
			if($currentHeight < $height)
			{
				$this->increaseHeight($height, $color);
			}
			elseif($currentHeight > $height)
			{
				$this->resize(null, $height);
				$this->increaseWidth($width, $color);
			}
		}
		elseif($beforeHeight != $height)
		{
			// Standardize height
			$this->resize(null, $height);
			$currentWidth = $this->getWidth();
			if($currentWidth < $width)
			{
				$this->increaseWidth($width, $color);
			}
			elseif($currentWidth > $width)
			{
				$this->resize($width, null);
				$this->increaseHeight($height, $color);
			}
		}

		// Check the result
		$afterWidth = $this->getWidth();
		$afterHeight = $this->getHeight();
		if($afterWidth != $width || $afterHeight != $height)
		{
			throw new \Exception("Falha ao padronizar o tamanho da imagem de {$beforeWidth}x{$beforeHeight} para {$width}x{$height}.");
		}

		return $this;
	}

	public function standardizeSize(int $width, int $height, array $color = self::COLOR): self
	{
		$this->exec([$this, '_standardizeSize'], [$width, $height, $color]);

		return $this;
	}

	protected function _increaseWidth(int $width, array $color): self
	{
		$currentWidth = $this->getWidth();
		$currentHeight = $this->getHeight();
		if($width > $currentWidth)
		{
			// Generate new GD image (same height, new width)
			$newImage = imagecreatetruecolor($width, $currentHeight);

			// Fill background
			$backgroundColor = imagecolorallocatealpha($newImage, $color['red'], $color['green'], $color['blue'], $color['alpha']);
			imagecolortransparent($this->image, $backgroundColor);
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
			imagefilledrectangle($newImage, 0, 0, $width, $currentHeight, $backgroundColor);

			// Resize
			$dst_x = ($width - $currentWidth) / 2;
			imagecopy($newImage, $this->image, $dst_x, 0, 0, 0, $currentWidth, $currentHeight);

			// Swap out the new image
			$this->image = $newImage;
		}
		return $this;
	}

	public function increaseWidth(int $width, array $color = self::COLOR): self
	{
		$this->exec([$this, '_increaseWidth'], [$width, $color]);

		return $this;
	}

	protected function _increaseHeight(int $height, array $color): self
	{
		$currentWidth = $this->getWidth();
		$currentHeight = $this->getHeight();
		if($height > $currentHeight)
		{
			// Generate new GD image (same width, new height)
			$newImage = imagecreatetruecolor($currentWidth, $height);

			// Fill background
			$backgroundColor = imagecolorallocatealpha($newImage, $color['red'], $color['green'], $color['blue'], $color['alpha']);
			imagecolortransparent($this->image, $backgroundColor);
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
			imagefilledrectangle($newImage, 0, 0, $currentWidth, $height, $backgroundColor);

			// Resize
			$dst_y = ($height - $currentHeight) / 2;
			imagecopy($newImage, $this->image, 0, $dst_y, 0, 0, $currentWidth, $currentHeight);

			// Swap out the new image
			$this->image = $newImage;
		}
		return $this;
	}

	public function increaseHeight(int $height, array $color = self::COLOR): self
	{
		$this->exec([$this, '_increaseHeight'], [$height, $color]);

		return $this;
	}

	public function resize($width = null, $height = null)
	{
		$this->exec('parent::resize', [$width, $height]);

		return $this;
	}

	public function toFile($file, $mimeType = null, $quality = 100)
	{
		if(!file_exists($file) || $this->changed)
		{
			parent::toFile($file, $mimeType, $quality);
		}

		return $this;
	}
}