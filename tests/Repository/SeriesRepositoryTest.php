<?php

namespace App\Tests\Repository;

use App\DTO\SeriesCreationInputDTO;
use App\Repository\EpisodeRepository;
use App\Repository\SeriesRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SeriesRepositoryTest extends KernelTestCase
{
    public function testSomething(): void
    {
        // Arrange
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $seriesRepository = $container->get(SeriesRepository::class);
        $episodeRepository = $container->get(EpisodeRepository::class);

        // Act
        // O método addDto não existia no curso. 
        $seriesRepository->addDto(new SeriesCreationInputDTO(
            'Series test',
            2, 
            5,
        ));

        $episodes = $episodeRepository->findAll();

        // Assert
        $this->assertSame('test', $kernel->getEnvironment());
        self::assertCount(10, $episodes);
    }
}
