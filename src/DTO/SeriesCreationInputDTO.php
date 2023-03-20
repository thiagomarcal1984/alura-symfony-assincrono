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
        
        // A validação de Assert\File funcionaria se o formulário
        // SeriesType não permitisse o mapeamento. Neste caso,
        // somente a validação em SeriesType funcionaria.
        
        // #[Assert\File(mimeTypes: 'image/*')] 
        // public ?File $coverImage = null,
        public ?string $coverImage = null,
    ) {
    }
}
