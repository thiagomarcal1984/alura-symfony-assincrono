<?php
namespace App\Messages;

use App\Entity\Series;

class SeriesWasDeleted
{
    public function __construct(
        public readonly Series $series
    ) {}
}
