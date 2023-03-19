<?php
namespace App\DTO;

// use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class SeriesCreationInputDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 5)]
        public string $seriesName = '',
        
        #[Assert\Positive] // Deve ser maior que zero.
        public int $seasonsQuantity = 0,
        
        #[Assert\Positive] // Deve ser maior que zero.
        public int $episodesPerSeason = 0,
        
        #[Assert\File] 
        // public ?File $coverImage = null,
        public ?string $coverImage = null,
    ) {
    }
}
