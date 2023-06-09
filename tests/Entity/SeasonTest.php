<?php

namespace App\Tests\Entity;

use App\Entity\Episode;
use App\Entity\Season;
use PHPUnit\Framework\TestCase;

class SeasonTest extends TestCase
{
    public function testGetWatchedEpisodes(): void
    {
        // Arrange
        $season = new Season(1);

        $episode1 = new Episode(1);
        $episode1->setWatched(true);

        $episode2 = new Episode(2);
        $episode2->setWatched(false);

        $season->addEpisode($episode1);
        $season->addEpisode($episode2);

        // Act
        $watchedEpisodes = $season->getWatchedEpisodes();

        // Assert
        // Só tem um episódio assistido...
        self::assertCount(1, $watchedEpisodes); 
        // ... e o episódio assistido é o primeiro.
        self::assertSame($episode1, $watchedEpisodes->first());
    }
}
